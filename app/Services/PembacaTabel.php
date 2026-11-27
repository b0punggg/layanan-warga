<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class PembacaTabel
{
    /**
     * @return list<array{baris: int, nama: string, nik: string, alamat: string}>
     */
    public function baca(UploadedFile $berkas): array
    {
        $ekstensi = strtolower($berkas->getClientOriginalExtension());

        if (! in_array($ekstensi, ['csv', 'txt', 'xlsx'], true)) {
            throw new RuntimeException('Gunakan berkas CSV atau Excel (.xlsx).');
        }

        if ($ekstensi === 'xlsx') {
            $mentah = $this->dariExcel($berkas->getRealPath());
        } else {
            $mentah = $this->dariCsv($berkas->getRealPath());
        }

        if ($mentah === []) {
            throw new RuntimeException('Berkas kosong.');
        }

        if (count($mentah) > 20000) {
            throw new RuntimeException('Berkas melebihi 20.000 baris.');
        }

        $judul = array_map(fn ($sel) => $this->kunci((string) $sel), $mentah[0]);
        $peta = [
            'nama' => array_search('nama', $judul, true),
            'nik' => array_search('nik', $judul, true),
            'alamat' => array_search('alamat', $judul, true),
        ];

        if (in_array(false, $peta, true)) {
            throw new RuntimeException('Baris pertama harus berisi kolom nama, nik, dan alamat.');
        }

        $hasil = [];

        foreach (array_slice($mentah, 1) as $indeks => $baris) {
            if ($this->barisKosong($baris)) {
                continue;
            }

            $hasil[] = [
                'baris' => $indeks + 2,
                'nama' => trim((string) ($baris[$peta['nama']] ?? '')),
                'nik' => trim((string) ($baris[$peta['nik']] ?? '')),
                'alamat' => trim((string) ($baris[$peta['alamat']] ?? '')),
            ];
        }

        return $hasil;
    }

    /**
     * @return list<list<mixed>>
     */
    private function dariCsv(string $path): array
    {
        $pegangan = fopen($path, 'rb');

        if ($pegangan === false) {
            throw new RuntimeException('Berkas tidak dapat dibaca.');
        }

        $baris = [];

        while (($rekam = fgetcsv($pegangan)) !== false) {
            if ($baris === [] && isset($rekam[0])) {
                $rekam[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $rekam[0]) ?? $rekam[0];
            }
            $baris[] = $rekam;
        }

        fclose($pegangan);

        return $baris;
    }

    /**
     * @return list<list<mixed>>
     */
    private function dariExcel(string $path): array
    {
        $buku = IOFactory::load($path);
        $lembar = $buku->getActiveSheet()->toArray(null, true, true, false);
        $buku->disconnectWorksheets();

        return $lembar;
    }

    private function kunci(string $nilai): string
    {
        $nilai = strtolower(trim($nilai));
        $nilai = str_replace([' ', '-'], '_', $nilai);

        return $nilai === 'alamat_asli' ? 'alamat' : $nilai;
    }

    /**
     * @param  list<mixed>  $baris
     */
    private function barisKosong(array $baris): bool
    {
        foreach ($baris as $sel) {
            if (trim((string) $sel) !== '') {
                return false;
            }
        }

        return true;
    }
}
