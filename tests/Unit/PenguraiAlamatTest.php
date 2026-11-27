<?php

namespace Tests\Unit;

use App\Services\PenguraiAlamat;
use PHPUnit\Framework\TestCase;

class PenguraiAlamatTest extends TestCase
{
    public function test_mengurai_dukuh_rt_rw(): void
    {
        $hasil = (new PenguraiAlamat)->uraikan('Dukuh Contoh RT 1 RW 2');

        $this->assertTrue($hasil['berhasil']);
        $this->assertSame('Dukuh Contoh', $hasil['dukuh']);
        $this->assertSame('001', $hasil['rt']);
        $this->assertSame('002', $hasil['rw']);
    }

    public function test_alamat_tanpa_rt_ditandai_gagal(): void
    {
        $hasil = (new PenguraiAlamat)->uraikan('Dukuh Tanpa Nomor');

        $this->assertFalse($hasil['berhasil']);
        $this->assertNull($hasil['rt']);
    }
}
