<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jenis_layanan')->where('kode', 'SKU')->update([
            'format_nomor' => '400.10.5/{nomor}',
            'skema_field_tambahan' => json_encode([
                ['nama' => 'jenis_usaha', 'label' => 'Jenis usaha', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'alamat_usaha', 'label' => 'Alamat usaha', 'tipe' => 'text', 'wajib' => true],
            ]),
        ]);

        DB::table('pejabat_kalurahan')->where('nama', 'Lurah (contoh)')->update([
            'nama' => 'Drs. H. Anang Zamroni, M.SI',
        ]);

        DB::table('users')
            ->where('email', 'lurah@kalurahan.local')
            ->where('name', 'Lurah (contoh)')
            ->update(['name' => 'Drs. H. Anang Zamroni, M.SI']);
    }

    public function down(): void
    {
        DB::table('jenis_layanan')->where('kode', 'SKU')->update([
            'format_nomor' => null,
            'skema_field_tambahan' => json_encode([
                ['nama' => 'nama_usaha', 'label' => 'Nama usaha', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'jenis_usaha', 'label' => 'Jenis usaha', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'alamat_usaha', 'label' => 'Alamat usaha', 'tipe' => 'text', 'wajib' => true],
            ]),
        ]);

        DB::table('pejabat_kalurahan')->where('nama', 'Drs. H. Anang Zamroni, M.SI')->update([
            'nama' => 'Lurah (contoh)',
        ]);
    }
};
