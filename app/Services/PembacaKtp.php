<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Throwable;

class PembacaKtp
{
    /**
     * Membaca teks di komputer kalurahan. Tidak mengirim foto ke layanan luar.
     */
    public function teks(string $path): ?string
    {
        if (config('surat.pembaca_ktp') === 'mati' || ! is_file($path)) {
            return null;
        }

        try {
            $tesseract = $this->tesseract($path);

            if ($tesseract !== null) {
                return $tesseract;
            }

            if (PHP_OS_FAMILY === 'Windows') {
                return $this->windows($path);
            }
        } catch (Throwable $e) {
            logger()->warning('pembaca ktp gagal', ['pesan' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Hanya nilai yang polanya jelas. Nama tidak diambil dari foto.
     *
     * @return array<string, string>
     */
    public function uraikan(string $teks): array
    {
        $baris = $this->baris($teks);
        $datar = implode(' ', $baris);
        $hasil = [];
        $nik = $this->nik($datar);

        if ($nik !== null) {
            $hasil['nik'] = $nik;
        }

        $tempat = $this->tempatLahir($baris, $datar);

        if ($tempat !== null) {
            $hasil['tempat_lahir'] = $tempat;
        }

        $agama = $this->agama($this->nilai($baris, 'Agama') ?? $this->cocok($datar, '/\bAgama\s*[:\-]?\s*(Islam|Kristen|Katolik|Hindu|Buddha|Budha|Khonghucu|Konghucu)\b/iu'));

        if ($agama !== null) {
            $hasil['agama'] = $agama;
        }

        $kawin = $this->kawin($this->nilai($baris, 'Status\s*Perkawinan') ?? $this->cocok($datar, '/Status\s*Perkawinan\s*[:\-]?\s*(Belum\s+Kawin|Cerai\s+Hidup|Cerai\s+Mati|Kawin)\b/iu'));

        if ($kawin !== null) {
            $hasil['status_kawin'] = $kawin;
        }

        $kerja = $this->pekerjaan($this->nilai($baris, 'Pekerjaan') ?? $this->cocok($datar, '/\bPekerjaan\s*[:\-]?\s*([A-Za-z][A-Za-z .]{1,40}?)(?=\s+(?:Kewarganegaraan|Berlaku|Gol\b|Status\b|Agama\b|NIK\b)|$)/iu'));

        if ($kerja !== null) {
            $hasil['pekerjaan'] = $kerja;
        }

        $alamat = $this->alamat($baris, $datar);

        if ($alamat !== null) {
            $hasil['alamat_lengkap'] = $alamat;
        }

        return $hasil;
    }

    private function tesseract(string $path): ?string
    {
        if (! $this->ada('tesseract')) {
            return null;
        }

        $proses = new Process(['tesseract', $path, 'stdout', '--psm', '6']);
        $proses->setTimeout(20);
        $proses->run();

        if (! $proses->isSuccessful()) {
            return null;
        }

        $teks = trim($proses->getOutput());

        return $teks !== '' ? $teks : null;
    }

    private function windows(string $path): ?string
    {
        $skrip = base_path('resources/skrip/baca-ktp.ps1');

        if (! is_file($skrip)) {
            return null;
        }

        $proses = new Process([
            'powershell.exe',
            '-NoProfile',
            '-NonInteractive',
            '-ExecutionPolicy', 'Bypass',
            '-File', $skrip,
            $path,
        ]);
        $proses->setTimeout(25);
        $proses->run();

        if (! $proses->isSuccessful()) {
            return null;
        }

        $teks = trim($proses->getOutput());

        return $teks !== '' ? $teks : null;
    }

    private function ada(string $perintah): bool
    {
        $proses = new Process(PHP_OS_FAMILY === 'Windows' ? ['where', $perintah] : ['which', $perintah]);
        $proses->setTimeout(5);
        $proses->run();

        return $proses->isSuccessful();
    }

    /**
     * @return list<string>
     */
    private function baris(string $teks): array
    {
        $teks = str_replace("\u{00A0}", ' ', $teks);
        $potong = preg_split('/\R/u', $teks) ?: [];

        return array_values(array_filter(array_map(
            fn (string $baris): string => trim((string) preg_replace('/\s+/u', ' ', $baris)),
            $potong,
        ), fn (string $baris): bool => $baris !== ''));
    }

    /**
     * @param  list<string>  $baris
     */
    private function nilai(array $baris, string $label): ?string
    {
        foreach ($baris as $indeks => $kalimat) {
            if (preg_match('/^'.$label.'\s*[:\-]?\s*(.*)$/iu', $kalimat, $cocok) !== 1) {
                continue;
            }

            $sisa = trim($cocok[1]);

            if ($sisa !== '') {
                return $sisa;
            }

            $berikut = trim($baris[$indeks + 1] ?? '');

            if ($berikut !== '' && ! $this->label($berikut)) {
                return $berikut;
            }
        }

        return null;
    }

    private function label(string $kalimat): bool
    {
        return preg_match('/^(NIK|Nama|Tempat|Jenis Kelamin|Gol\.?\s*Darah|Alamat|RT\/RW|Kel\/Desa|Kecamatan|Agama|Status|Pekerjaan|Kewarganegaraan|Berlaku)\b/iu', $kalimat) === 1;
    }

    private function nik(string $datar): ?string
    {
        if (preg_match('/\bNIK\s*[:\-]?\s*(\d{16})\b/iu', $datar, $cocok) === 1) {
            return $cocok[1];
        }

        preg_match_all('/\b(\d{16})\b/', $datar, $semua);
        $unik = array_values(array_unique($semua[1] ?? []));

        return count($unik) === 1 ? $unik[0] : null;
    }

    /**
     * @param  list<string>  $baris
     */
    private function tempatLahir(array $baris, string $datar): ?string
    {
        $mentah = $this->nilai($baris, 'Tempat\s*\/\s*Tgl\.?\s*Lahir')
            ?? $this->cocok($datar, '/Tempat\s*\/\s*Tgl\.?\s*Lahir\s*[:\-]?\s*([A-Za-z][A-Za-z .\'-]{1,40}?)\s*,\s*\d{2}[-.\/]\d{2}[-.\/]\d{4}/iu');

        if ($mentah === null) {
            return null;
        }

        $nama = trim((string) preg_replace('/,?\s*\d{2}[-.\/]\d{2}[-.\/]\d{4}.*/', '', $mentah));
        $nama = trim($nama, " \t,.-");

        if (preg_match('/^[A-Za-z][A-Za-z .\'-]{1,40}$/', $nama) !== 1) {
            return null;
        }

        return mb_convert_case(mb_strtolower($nama), MB_CASE_TITLE, 'UTF-8');
    }

    private function agama(?string $mentah): ?string
    {
        $kunci = mb_strtolower(trim((string) $mentah));
        $peta = [
            'islam' => 'Islam',
            'kristen' => 'Kristen',
            'katolik' => 'Katolik',
            'hindu' => 'Hindu',
            'buddha' => 'Buddha',
            'budha' => 'Buddha',
            'khonghucu' => 'Khonghucu',
            'konghucu' => 'Khonghucu',
        ];

        return $peta[$kunci] ?? null;
    }

    private function kawin(?string $mentah): ?string
    {
        $kunci = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) $mentah)));
        $peta = [
            'kawin' => 'Kawin',
            'belum kawin' => 'Belum kawin',
            'cerai hidup' => 'Cerai hidup',
            'cerai mati' => 'Cerai mati',
        ];

        return $peta[$kunci] ?? null;
    }

    private function pekerjaan(?string $mentah): ?string
    {
        $nama = trim((string) preg_replace('/\s+/u', ' ', (string) $mentah));

        if (preg_match('/^[A-Za-z][A-Za-z .]{1,40}$/', $nama) !== 1 || $this->label($nama)) {
            return null;
        }

        return mb_convert_case(mb_strtolower($nama), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @param  list<string>  $baris
     */
    private function alamat(array $baris, string $datar): ?string
    {
        $jalan = $this->nilai($baris, 'Alamat');
        $rtRw = $this->nilai($baris, 'RT\/RW') ?? $this->cocok($datar, '/\bRT\/RW\s*[:\-]?\s*(\d{1,3}\s*\/\s*\d{1,3})\b/iu');

        if ($jalan !== null) {
            $jalan = trim((string) preg_replace('/\s+RT\/RW\b.*$/iu', '', $jalan));
        }

        if ($jalan === null || preg_match('/^[A-Za-z0-9][A-Za-z0-9 .,\-\/]{4,80}$/', $jalan) !== 1 || $this->label($jalan)) {
            return null;
        }

        if ($rtRw !== null && preg_match('/^(\d{1,3})\s*\/\s*(\d{1,3})$/', trim($rtRw), $cocok) === 1) {
            return $jalan.' RT '.str_pad($cocok[1], 3, '0', STR_PAD_LEFT).' RW '.str_pad($cocok[2], 3, '0', STR_PAD_LEFT);
        }

        return $jalan;
    }

    private function cocok(string $teks, string $pola): ?string
    {
        if (preg_match($pola, $teks, $cocok) !== 1) {
            return null;
        }

        return trim($cocok[1]);
    }
}
