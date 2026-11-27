<?php

namespace Tests\Unit;

use App\Services\PembacaKtp;
use App\Services\PembacaNik;
use Tests\TestCase;

class PembacaIdentitasTest extends TestCase
{
    public function test_nik_perempuan_dan_laki_laki(): void
    {
        $pembaca = app(PembacaNik::class);

        $this->assertSame([
            'tanggal_lahir' => '1990-01-11',
            'jenis_kelamin' => 'Perempuan',
        ], $pembaca->baca('3404125101900001'));

        $this->assertSame([
            'tanggal_lahir' => '1990-01-11',
            'jenis_kelamin' => 'Laki-laki',
        ], $pembaca->baca('3404121101900001'));
    }

    public function test_nik_tahun_depan_dihitung_sebagai_abad_lalu(): void
    {
        $hasil = app(PembacaNik::class)->baca('3404120101300001');

        $this->assertSame('1930-01-01', $hasil['tanggal_lahir']);
        $this->assertSame('Laki-laki', $hasil['jenis_kelamin']);
    }

    public function test_nik_tanpa_tanggal_sah_tidak_mengisi(): void
    {
        $hasil = app(PembacaNik::class)->baca('3400000000000077');

        $this->assertNull($hasil['tanggal_lahir']);
        $this->assertNull($hasil['jenis_kelamin']);
    }

    public function test_teks_ktp_yang_jelas_terurai(): void
    {
        $teks = <<<'TXT'
NIK
3404125101900001
Nama
SULASTRI
Tempat/Tgl Lahir
SLEMAN, 01-01-1990
Alamat
SOROBAYAN
RT/RW
001/002
Agama
ISLAM
Status Perkawinan
KAWIN
Pekerjaan
WIRASWASTA
Kewarganegaraan
WNI
TXT;

        $hasil = app(PembacaKtp::class)->uraikan($teks);

        $this->assertSame('3404125101900001', $hasil['nik']);
        $this->assertSame('Sleman', $hasil['tempat_lahir']);
        $this->assertSame('Islam', $hasil['agama']);
        $this->assertSame('Kawin', $hasil['status_kawin']);
        $this->assertSame('Wiraswasta', $hasil['pekerjaan']);
        $this->assertSame('SOROBAYAN RT 001 RW 002', $hasil['alamat_lengkap']);
        $this->assertArrayNotHasKey('nama', $hasil);
    }

    public function test_teks_satu_baris_dan_nik_ganda_tidak_ditebak(): void
    {
        $pembaca = app(PembacaKtp::class);
        $satu = $pembaca->uraikan('NIK 3404125101900001 Tempat/Tgl Lahir SLEMAN, 01-01-1990 Agama ISLAM Status Perkawinan BELUM KAWIN Pekerjaan BURUH Kewarganegaraan WNI');

        $this->assertSame('3404125101900001', $satu['nik']);
        $this->assertSame('Sleman', $satu['tempat_lahir']);
        $this->assertSame('Islam', $satu['agama']);
        $this->assertSame('Belum kawin', $satu['status_kawin']);
        $this->assertSame('Buruh', $satu['pekerjaan']);

        $ganda = $pembaca->uraikan('1234567890123456 dan 1111111111111111');

        $this->assertArrayNotHasKey('nik', $ganda);
        $this->assertArrayNotHasKey('agama', $ganda);
    }
}
