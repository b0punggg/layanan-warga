<?php

namespace Tests\Feature;

use App\Services\BerkasPrivat;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BerkasPrivatTest extends TestCase
{
    public function test_berkas_tersimpan_terenkripsi_di_luar_web(): void
    {
        Storage::fake('berkas');

        $isi = "foto-ktp-\x00\xFF";
        $path = app(BerkasPrivat::class)->simpan($isi, 'ktp/contoh.bin');

        $mentah = Storage::disk('berkas')->get($path);

        $this->assertIsString($mentah);
        $this->assertStringNotContainsString('foto-ktp', $mentah);
        $this->assertSame($isi, app(BerkasPrivat::class)->baca($path));
        $this->assertFalse(str_starts_with($path, 'public'));
    }
}
