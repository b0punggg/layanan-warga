<?php

namespace Database\Seeders;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Enums\StatusDataPenduduk;
use App\Enums\StatusVerifikasiProfil;
use App\Models\JenisLayanan;
use App\Models\PejabatKalurahan;
use App\Models\Penduduk;
use App\Models\ProfilWarga;
use App\Models\User;
use App\Support\KatalogLayanan;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $sandi = (string) config('surat.kata_sandi_percobaan');

        User::query()->create([
            'name' => 'Admin Kalurahan',
            'email' => 'admin@kalurahan.local',
            'peran' => Peran::Admin,
            'status_akun' => StatusAkun::Aktif,
            'password' => $sandi,
        ]);

        $operator = User::query()->create([
            'name' => 'Operator Kalurahan',
            'email' => 'operator@kalurahan.local',
            'peran' => Peran::Operator,
            'status_akun' => StatusAkun::Aktif,
            'password' => $sandi,
        ]);

        $lurah = User::query()->create([
            'name' => 'Drs. H. Anang Zamroni, M.SI',
            'email' => 'lurah@kalurahan.local',
            'peran' => Peran::Lurah,
            'status_akun' => StatusAkun::Aktif,
            'password' => $sandi,
        ]);

        $warga = User::query()->create([
            'name' => 'Sulastri',
            'email' => 'warga@kalurahan.local',
            'nik' => '3400000000000001',
            'no_hp' => '081234567890',
            'peran' => Peran::Warga,
            'status_akun' => StatusAkun::Aktif,
            'password' => $sandi,
        ]);

        $penduduk = Penduduk::query()->create([
            'nik' => '3400000000000001',
            'nama' => 'Sulastri',
            'alamat_asli' => 'Dukuh Contoh RT 001 RW 002',
            'status_data' => StatusDataPenduduk::Menunggu,
            'sumber_data' => 'contoh-fase-0',
        ]);

        ProfilWarga::query()->create([
            'penduduk_id' => $penduduk->id,
            'user_id' => $warga->id,
            'tempat_lahir' => 'Sleman',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Perempuan',
            'agama' => 'Islam',
            'pekerjaan' => 'Wiraswasta',
            'no_hp' => '081234567890',
            'email' => 'warga@kalurahan.local',
            'alamat_lengkap' => 'Dukuh Contoh, RT 001/RW 002',
            'dukuh' => 'Contoh',
            'rt' => '001',
            'rw' => '002',
            'status_verifikasi' => StatusVerifikasiProfil::Disetujui,
            'diverifikasi_oleh' => $operator->id,
            'tanggal_verifikasi' => now(),
        ]);

        PejabatKalurahan::query()->create([
            'user_id' => $lurah->id,
            'nama' => 'Drs. H. Anang Zamroni, M.SI',
            'jabatan' => 'Lurah',
            'aktif' => true,
        ]);

        User::query()->create([
            'name' => 'Ketua RT 001 RW 002',
            'email' => 'rt@kalurahan.local',
            'peran' => Peran::RtRw,
            'status_akun' => StatusAkun::Aktif,
            'dukuh' => 'Contoh',
            'rt' => '001',
            'rw' => '002',
            'password' => $sandi,
        ]);

        $this->jenisLayanan();
        KatalogLayanan::faseDua();
        KatalogLayanan::faseTiga();
    }

    private function jenisLayanan(): void
    {
        $syarat = [
            ['nama' => 'ktp', 'label' => 'Foto KTP', 'wajib' => true],
            ['nama' => 'kk', 'label' => 'Foto Kartu Keluarga', 'wajib' => false],
        ];

        JenisLayanan::query()->create([
            'nama' => 'Surat Keterangan Usaha',
            'kode' => 'SKU',
            'deskripsi' => 'Keterangan bagi warga yang memiliki usaha.',
            'butuh_pengantar_rt' => false,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => $syarat,
            'format_nomor' => '400.10.5/{nomor}',
            'skema_field_tambahan' => [
                ['nama' => 'jenis_usaha', 'label' => 'Jenis usaha', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'alamat_usaha', 'label' => 'Alamat usaha', 'tipe' => 'text', 'wajib' => true],
            ],
            'template_surat' => 'surat.templates.sku',
            'estimasi_hari' => 3,
            'aktif' => true,
        ]);

        JenisLayanan::query()->create([
            'nama' => 'Surat Keterangan Domisili',
            'kode' => 'DOM',
            'deskripsi' => 'Keterangan tempat tinggal warga di kalurahan.',
            'butuh_pengantar_rt' => false,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => $syarat,
            'skema_field_tambahan' => [
                ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
            ],
            'template_surat' => 'surat.templates.domisili',
            'estimasi_hari' => 2,
            'aktif' => true,
        ]);

        JenisLayanan::query()->create([
            'nama' => 'Surat Keterangan Tidak Mampu Pendidikan',
            'kode' => 'SKTM',
            'deskripsi' => 'Keterangan tidak mampu untuk keperluan pendidikan, bagi yang terdaftar atau tidak terdaftar dalam Kartu Keluarga Miskin atau Kartu Keluarga Rentan Miskin.',
            'butuh_pengantar_rt' => false,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => $syarat,
            'format_nomor' => '400.3/{nomor}',
            'skema_field_tambahan' => [
                ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'nama_anak', 'label' => 'Nama anak', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'nik_anak', 'label' => 'NIK anak', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'ttl_anak', 'label' => 'Tempat, tanggal lahir anak', 'tipe' => 'text', 'wajib' => true],
            ],
            'template_surat' => 'surat.templates.sktm',
            'estimasi_hari' => 3,
            'aktif' => true,
        ]);

        JenisLayanan::query()->create([
            'nama' => 'Surat Keterangan Tidak Mampu Kesehatan',
            'kode' => 'SKKES',
            'deskripsi' => 'Keterangan tidak mampu untuk keperluan kesehatan, bagi yang termasuk atau tidak termasuk dalam Kartu Keluarga Miskin dan Kartu Keluarga Rentan Miskin.',
            'butuh_pengantar_rt' => false,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => $syarat,
            'format_nomor' => '400.7/{nomor}',
            'skema_field_tambahan' => [
                ['nama' => 'tujuan', 'label' => 'Tujuan', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
            ],
            'template_surat' => 'surat.templates.sktm-kesehatan',
            'estimasi_hari' => 3,
            'aktif' => true,
        ]);
    }
}
