<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jenis_layanan')->where('kode', 'SKTM')->update([
            'deskripsi' => 'Keterangan keluarga tidak mampu, bagi yang terdaftar atau tidak terdaftar dalam Kartu Keluarga Miskin atau Kartu Keluarga Rentan Miskin.',
            'format_nomor' => '400.3/{nomor}',
            'naskah' => null,
            'skema_field_tambahan' => json_encode([
                ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'nama_anak', 'label' => 'Nama anak', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'nik_anak', 'label' => 'NIK anak', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'ttl_anak', 'label' => 'Tempat, tanggal lahir anak', 'tipe' => 'text', 'wajib' => true],
            ]),
        ]);
    }

    public function down(): void
    {
        DB::table('jenis_layanan')->where('kode', 'SKTM')->update([
            'deskripsi' => 'Keterangan untuk keperluan bantuan atau keringanan.',
            'format_nomor' => null,
            'skema_field_tambahan' => json_encode([
                ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
            ]),
        ]);
    }
};
