<?php

namespace App\Services;

use App\Models\JenisLayanan;
use App\Models\Pengajuan;
use App\Support\IsianSurat;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;

class PenulisSuratWord
{
    public function __construct(private PenerbitSurat $penerbit) {}

    public function buat(Pengajuan $pengajuan): string
    {
        $pengajuan->loadMissing(['jenisLayanan', 'pemohon.profilWarga.penduduk', 'surat']);
        $nomor = $pengajuan->surat?->nomor_surat ?? '………………';
        $data = $this->penerbit->dataWarga($pengajuan);
        $tanggal = ($pengajuan->surat?->tanggal_terbit ?? now())->translatedFormat('d F Y');

        return $this->susun(
            $pengajuan->jenisLayanan->tampilanSurat(),
            $pengajuan->jenisLayanan->nama,
            $pengajuan->jenisLayanan->naskah,
            $data,
            $nomor,
            $tanggal,
        );
    }

    /**
     * @param  array<string, string>  $data
     */
    public function contoh(JenisLayanan $jenis, string $nomor): string
    {
        return $this->susun(
            $jenis->tampilanSurat(),
            $jenis->nama,
            $jenis->naskah,
            $this->dataContoh(),
            $nomor,
            now()->translatedFormat('d F Y'),
        );
    }

    /**
     * @param  array<string, string>  $data
     */
    private function susun(string $tampilan, string $namaLayanan, ?string $naskah, array $data, string $nomor, string $tanggal): string
    {
        $dokumen = new PhpWord;
        $dokumen->setDefaultFontName('Arial');
        $dokumen->setDefaultFontSize(12);

        $halaman = $dokumen->addSection([
            'pageSizeW' => 12242,
            'pageSizeH' => 18722,
            'marginTop' => 1276,
            'marginRight' => 902,
            'marginBottom' => 1276,
            'marginLeft' => 1418,
        ]);

        $this->kop($halaman);
        $this->tubuh($halaman, $tampilan, $namaLayanan, $naskah, $data, $nomor);
        $this->tandaTangan($halaman, $tanggal);

        $sementara = tempnam(sys_get_temp_dir(), 'surat');
        IOFactory::createWriter($dokumen, 'Word2007')->save($sementara);
        $isi = (string) file_get_contents($sementara);
        @unlink($sementara);

        return $isi;
    }

    private function kop(Section $halaman): void
    {
        $tabel = $halaman->addTable([
            'unit' => TblWidth::TWIP,
            'width' => 10200,
            'alignment' => Jc::CENTER,
        ]);
        $tabel->addRow();
        $lambang = public_path('kop/lambang-sleman.png');
        $selLambang = $tabel->addCell(1700);
        if (is_file($lambang)) {
            $selLambang->addImage($lambang, ['width' => 68, 'height' => 74, 'alignment' => Jc::CENTER]);
        }

        $teks = $tabel->addCell(8500);
        foreach ([
            'PEMERINTAH KABUPATEN '.mb_strtoupper((string) config('surat.kabupaten')),
            'KAPANEWON '.mb_strtoupper((string) config('surat.kapanewon')),
        ] as $baris) {
            $teks->addText($baris, ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }
        $teks->addText(
            'PEMERINTAH KALURAHAN '.mb_strtoupper((string) config('surat.kalurahan')),
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60],
        );
        $hiasan = public_path('kop/hiasan-kop.png');
        if (is_file($hiasan)) {
            $teks->addImage($hiasan, ['width' => 250, 'height' => 22, 'alignment' => Jc::CENTER]);
        }

        $tengah = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
        $halaman->addText('Alamat: '.config('surat.alamat_kalurahan'), ['size' => 10], $tengah);
        $surel = $halaman->addTextRun(['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
        $surel->addText('Laman: '.config('surat.laman').', Surel: ', ['size' => 10]);
        $surel->addText((string) config('surat.surel'), ['size' => 10, 'underline' => 'single']);
        $halaman->addText('', [], ['spaceAfter' => 80, 'borderBottomSize' => 18, 'borderBottomColor' => '111111']);
    }

    /**
     * @param  array<string, string>  $data
     */
    private function tubuh(Section $halaman, string $tampilan, string $namaLayanan, ?string $naskah, array $data, string $nomor): void
    {
        if ($tampilan === 'surat.templates.dinamis') {
            $data['nomor'] = $nomor;
            $teks = trim($this->naskahPolos($naskah, $data));
            if ($teks === '') {
                $this->judul($halaman, mb_strtoupper($namaLayanan), $nomor);

                return;
            }
            foreach (preg_split("/\R+/", $teks) ?: [] as $paragraf) {
                if (trim($paragraf) !== '') {
                    $this->paragraf($halaman, trim($paragraf));
                }
            }

            return;
        }

        match ($tampilan) {
            'surat.templates.sku' => $this->sku($halaman, $data, $nomor),
            'surat.templates.skck' => $this->skck($halaman, $data, $nomor),
            'surat.templates.penghasilan' => $this->penghasilan($halaman, $data, $nomor),
            'surat.templates.sktm' => $this->sktm($halaman, $data, $nomor),
            'surat.templates.sktm-kesehatan' => $this->sktmKesehatan($halaman, $data, $nomor),
            'surat.templates.domisili' => $this->domisili($halaman, $data, $nomor),
            default => $this->umum($halaman, $namaLayanan, $data, $nomor),
        };
    }

    /**
     * @param  array<string, string>  $data
     */
    private function sku(Section $halaman, array $data, string $nomor): void
    {
        $this->judul($halaman, 'SURAT KETERANGAN USAHA', $nomor, 'Nomor : ');
        $this->paragraf($halaman, 'Pemerintah Kalurahan '.config('surat.kalurahan').', Kapanewon '.config('surat.kapanewon').', Kabupaten '.config('surat.kabupaten').', dengan ini menerangkan bahwa :');
        $this->baris($halaman, [
            'Nama Lengkap' => $data['nama'],
            'Nomor Induk Kependudukan ( NIK )' => $data['nik'],
            'Tempat Dan Tanggal Lahir' => $data['tempat_lahir'].', '.$data['tanggal_lahir'],
            'Jenis Kelamin' => $data['jenis_kelamin'],
            'Warga Negara' => $data['warga_negara'] ?? 'Indonesia',
            'Agama' => $data['agama'] ?? '—',
            'Pekerjaan' => $data['pekerjaan'],
            'Alamat' => $data['alamat'],
            'Jenis Usaha' => $data['jenis_usaha'],
            'Alamat Usaha' => $data['alamat_usaha'],
        ]);
        $this->paragraf($halaman, 'Demikian surat keterangan ini kami buat, agar dapat dipergunakan sebagaimana mestinya.');
    }

    /**
     * @param  array<string, string>  $data
     */
    private function skck(Section $halaman, array $data, string $nomor): void
    {
        $this->judul($halaman, 'SURAT PENGANTAR SKCK', $nomor);
        $this->pembuka($halaman);
        $this->identitasUmum($halaman, $data, true);
        $this->paragraf($halaman, 'Surat pengantar ini dibuat untuk keperluan '.($data['keperluan'] ?? '—').'.');
    }

    /**
     * @param  array<string, string>  $data
     */
    private function penghasilan(Section $halaman, array $data, string $nomor): void
    {
        $this->judul($halaman, 'SURAT KETERANGAN PENGHASILAN', $nomor);
        $this->pembuka($halaman);
        $this->identitasUmum($halaman, $data, false);
        $this->paragraf($halaman, 'Menurut sepengetahuan kami, perkiraan penghasilan yang bersangkutan sebesar '.($data['jumlah_penghasilan'] ?? '—').' per bulan.');
        $this->paragraf($halaman, 'Surat keterangan ini dibuat untuk keperluan '.($data['keperluan'] ?? '—').'.');
    }

    /**
     * @param  array<string, string>  $data
     */
    private function sktm(Section $halaman, array $data, string $nomor): void
    {
        $this->judul($halaman, 'SURAT KETERANGAN TIDAK MAMPU', $nomor, 'Nomor : ');
        $this->paragraf($halaman, 'Saya, yang bertanda tangan dibawah ini :');
        $this->baris($halaman, [
            'Nama' => 'Drs. Anang Zamroni, M.S.I.',
            'Jabatan' => 'Lurah '.config('surat.kalurahan'),
        ]);
        $this->paragraf($halaman, 'dengan ini menerangkan bahwa :');
        $this->baris($halaman, [
            'Nama Lengkap' => $data['nama'],
            'NIK' => $data['nik'],
            'Tempat dan tanggal lahir' => $data['tempat_lahir'].', '.$data['tanggal_lahir'],
            'Pekerjaan' => $data['pekerjaan'],
            'Alamat' => $data['alamat'],
        ]);
        $status = app(IsianSurat::class)->frasaStatusKartu($data['status_kartu'] ?? null);
        $this->paragraf($halaman, 'bahwa saudara tersebut diatas adalah keluarga tidak mampu dan berpenghasilan rendah yang '.$status.' dalam Kartu Keluarga Miskin atau Kartu Keluarga Rentan Miskin. Surat keterangan ini dibuat untuk '.($data['keperluan'] ?? '—').':');
        $this->baris($halaman, [
            'Nama' => $data['nama_anak'] ?? '—',
            'NIK' => $data['nik_anak'] ?? '—',
            'Tempat, Tgl Lahir' => $data['ttl_anak'] ?? '—',
        ]);
        $this->paragraf($halaman, 'Demikian surat keterangan ini diberikan kepada yang bersangkutan untuk dapat dipergunakan sebagaimana mestinya.');
    }

    /**
     * @param  array<string, string>  $data
     */
    private function sktmKesehatan(Section $halaman, array $data, string $nomor): void
    {
        $nilaiKartu = $data['status_kartu_kesehatan'] ?? null;
        if ($nilaiKartu === null || $nilaiKartu === '' || $nilaiKartu === '—') {
            $nilaiKartu = $data['status_kartu'] ?? null;
        }
        $status = app(IsianSurat::class)->frasaTermasukKartu($nilaiKartu);
        $keterangan = 'Menerangkan orang tersebut diatas termasuk dalam keluarga tidak mampu dan berpenghasilan rendah yang '.$status.' dalam Kartu Keluarga Miskin dan Kartu Keluarga Rentan Miskin.';

        $this->judul($halaman, 'SURAT KETERANGAN TIDAK MAMPU', $nomor, 'Nomor : ');
        $this->paragraf($halaman, 'Pemerintah Kalurahan '.config('surat.kalurahan').', Kapanewon '.config('surat.kapanewon').', Kabupaten '.config('surat.kabupaten').', dengan ini menerangkan bahwa :');
        $this->baris($halaman, [
            'Nama lengkap' => $data['nama'],
            'Nomor Induk Kependudukan ( NIK )' => $data['nik'],
            'Tempat dan tanggal lahir' => $data['tempat_lahir'].', '.$data['tanggal_lahir'],
            'Jenis kelamin' => $data['jenis_kelamin'],
            'Warga negara' => $data['warga_negara'] ?? 'Indonesia',
            'Agama' => $data['agama'] ?? '—',
            'Pekerjaan' => $data['pekerjaan'],
            'Alamat' => $data['alamat'],
            'Tujuan' => $data['tujuan'] ?? '—',
            'Keperluan' => $data['keperluan'] ?? '—',
            'Keterangan' => $keterangan,
        ]);
        $this->paragraf($halaman, 'Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.');
    }

    /**
     * @param  array<string, string>  $data
     */
    private function domisili(Section $halaman, array $data, string $nomor): void
    {
        $this->judul($halaman, 'SURAT KETERANGAN DOMISILI', $nomor);
        $this->pembuka($halaman);
        $this->identitasUmum($halaman, $data, true);
        $this->paragraf($halaman, 'Benar yang bersangkutan berdomisili di alamat tersebut dan merupakan warga Kalurahan '.config('surat.kalurahan').'.');
        $this->paragraf($halaman, 'Surat keterangan ini dibuat untuk keperluan '.($data['keperluan'] ?? '—').'.');
    }

    /**
     * @param  array<string, string>  $data
     */
    private function umum(Section $halaman, string $namaLayanan, array $data, string $nomor): void
    {
        $this->judul($halaman, mb_strtoupper($namaLayanan), $nomor);
        $this->pembuka($halaman);
        $this->identitasUmum($halaman, $data, true);
    }

    private function pembuka(Section $halaman): void
    {
        $this->paragraf($halaman, 'Yang bertanda tangan di bawah ini, Lurah '.config('surat.kalurahan').', menerangkan bahwa:');
    }

    /**
     * @param  array<string, string>  $data
     */
    private function identitasUmum(Section $halaman, array $data, bool $denganKelamin): void
    {
        $baris = [
            'Nama' => $data['nama'],
            'NIK' => $data['nik'],
            'Tempat, tanggal lahir' => $data['tempat_lahir'].', '.$data['tanggal_lahir'],
        ];
        if ($denganKelamin) {
            $baris['Jenis kelamin'] = $data['jenis_kelamin'];
        }
        $baris['Pekerjaan'] = $data['pekerjaan'];
        $baris['Alamat'] = $data['alamat'];
        $this->baris($halaman, $baris);
    }

    private function judul(Section $halaman, string $judul, string $nomor, string $awalan = 'Nomor: '): void
    {
        $halaman->addText($judul, ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER, 'spaceBefore' => 200, 'spaceAfter' => 0]);
        $halaman->addText($awalan.$nomor, ['size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 160]);
    }

    private function paragraf(Section $halaman, string $teks): void
    {
        $halaman->addText($teks, ['size' => 12], ['alignment' => Jc::BOTH, 'spaceAfter' => 120]);
    }

    /**
     * @param  array<string, string>  $baris
     */
    private function baris(Section $halaman, array $baris): void
    {
        $tabel = $halaman->addTable(['unit' => TblWidth::TWIP, 'width' => 10200]);
        foreach ($baris as $label => $nilai) {
            $tabel->addRow();
            $tabel->addCell(4600)->addText($label, ['size' => 12], ['spaceAfter' => 0]);
            $tabel->addCell(300)->addText(':', ['size' => 12], ['spaceAfter' => 0]);
            $tabel->addCell(5300)->addText($nilai, ['size' => 12], ['spaceAfter' => 0]);
        }
        $halaman->addText('', [], ['spaceAfter' => 120]);
    }

    private function tandaTangan(Section $halaman, string $tanggal): void
    {
        $tabel = $halaman->addTable(['unit' => TblWidth::TWIP, 'width' => 9350]);
        $tabel->addRow();
        $tabel->addCell(4962);
        $sel = $tabel->addCell(4388);
        $kiri = ['spaceAfter' => 60];
        $huruf = ['name' => 'Arial', 'size' => 12];
        $sel->addText(config('surat.kalurahan').', '.$tanggal, $huruf, $kiri);
        $sel->addText('Lurah '.config('surat.kalurahan'), $huruf, $kiri);
        $penanda = public_path('kop/penanda-tte.png');
        $ukuran = Converter::cmToPixel(2.62);
        if (is_file($penanda)) {
            $sel->addImage($penanda, ['width' => $ukuran, 'height' => $ukuran]);
        }
        $sel->addText('[onshow.ttd_19254;ope=changepic;noerr]', [
            'name' => 'Times New Roman',
            'size' => 1,
            'underline' => 'single',
        ], ['spaceAfter' => 80]);
        $sel->addText('Drs. Anang Zamroni, M.S.I.', $huruf, $kiri);
    }

    /**
     * @param  array<string, string>  $data
     */
    /**
     * @return array<string, string>
     */
    public function dataContoh(): array
    {
        return [
            'nama' => 'Sulastri',
            'nik' => '3400000000000001',
            'tempat_lahir' => 'Sleman',
            'tanggal_lahir' => '1 Januari 1990',
            'jenis_kelamin' => 'Perempuan',
            'warga_negara' => 'Indonesia',
            'agama' => 'Islam',
            'pekerjaan' => 'Wiraswasta',
            'alamat' => 'Dukuh Contoh, RT 001/RW 002',
            'nama_usaha' => 'Warung Contoh',
            'jenis_usaha' => 'Perdagangan',
            'alamat_usaha' => 'Dukuh Contoh',
            'keperluan' => 'Kelengkapan administrasi',
            'status_kartu' => 'terdaftar',
            'status_kartu_kesehatan' => 'termasuk',
            'tujuan' => 'Berobat',
            'nik_anak' => '3400000000000002',
            'ttl_anak' => 'Sleman, 2 Februari 2015',
            'jumlah_penghasilan' => 'Rp 1.500.000',
            'status_perkawinan' => 'Belum kawin',
            'nama_pasangan' => '—',
            'nama_anak' => 'Budi Santoso',
            'hari_lahir' => '—',
            'nama_ayah' => '—',
            'nama_ibu' => '—',
            'nama_almarhum' => '—',
            'tanggal_meninggal' => '—',
            'hubungan' => '—',
            'jenis_perpindahan' => '—',
            'alamat_tujuan' => '—',
        ];
    }

    private function naskahPolos(?string $naskah, array $data): string
    {
        $teks = (string) $naskah;
        $teks = preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function (array $cocok) use ($data): string {
            return (string) ($data[strtolower($cocok[1])] ?? '—');
        }, $teks) ?? $teks;

        return trim(html_entity_decode(strip_tags($teks)));
    }
}
