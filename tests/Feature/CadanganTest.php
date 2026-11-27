<?php

namespace Tests\Feature;

use Tests\TestCase;

class CadanganTest extends TestCase
{
    public function test_cadangan_menolak_selain_mysql(): void
    {
        $this->artisan('layanan:cadangkan')
            ->expectsOutput('Cadangan ini hanya untuk koneksi MySQL.')
            ->assertFailed();
    }

    public function test_pemulihan_menolak_nama_basis_aneh(): void
    {
        $this->artisan('layanan:pulihkan', [
            'folder' => storage_path('framework'),
            '--basis' => 'layanan warga',
        ])->expectsOutputToContain('huruf, angka')->assertFailed();
    }

    public function test_pemulihan_menolak_menimpa_basis_aktif_tanpa_timpa(): void
    {
        config(['database.connections.mysql.database' => 'layanan_warga']);

        $this->artisan('layanan:pulihkan', [
            'folder' => storage_path('framework'),
            '--basis' => 'layanan_warga',
        ])->expectsOutputToContain('sedang dipakai')->assertFailed();
    }
}
