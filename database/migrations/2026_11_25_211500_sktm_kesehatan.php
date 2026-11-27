<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jenis_layanan')->where('kode', 'SKTM')->update([
            'nama' => 'Surat Keterangan Tidak Mampu Pendidikan',
            'deskripsi' => 'Keterangan tidak mampu untuk keperluan pendidikan, bagi yang terdaftar atau tidak terdaftar dalam Kartu Keluarga Miskin atau Kartu Keluarga Rentan Miskin.',
        ]);

        if (DB::table('jenis_layanan')->where('kode', 'SKKES')->exists()) {
            return;
        }

        $sekarang = now();
        DB::table('jenis_layanan')->insert([
            'nama' => 'Surat Keterangan Tidak Mampu Kesehatan',
            'kode' => 'SKKES',
            'deskripsi' => 'Keterangan tidak mampu untuk keperluan kesehatan, bagi yang termasuk atau tidak termasuk dalam Kartu Keluarga Miskin dan Kartu Keluarga Rentan Miskin.',
            'butuh_pengantar_rt' => false,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => json_encode([
                ['nama' => 'ktp', 'label' => 'Foto KTP', 'wajib' => true],
                ['nama' => 'kk', 'label' => 'Foto Kartu Keluarga', 'wajib' => false],
            ]),
            'skema_field_tambahan' => json_encode([
                ['nama' => 'tujuan', 'label' => 'Tujuan', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
            ]),
            'template_surat' => 'surat.templates.sktm-kesehatan',
            'format_nomor' => '400.7/{nomor}',
            'estimasi_hari' => 3,
            'aktif' => true,
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ]);
    }

    public function down(): void
    {
        DB::table('jenis_layanan')->where('kode', 'SKKES')->delete();
        DB::table('jenis_layanan')->where('kode', 'SKTM')->update([
            'nama' => 'Surat Keterangan Tidak Mampu',
            'deskripsi' => 'Keterangan keluarga tidak mampu, bagi yang terdaftar atau tidak terdaftar dalam Kartu Keluarga Miskin atau Kartu Keluarga Rentan Miskin.',
        ]);
    }
};
