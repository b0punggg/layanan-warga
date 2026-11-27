<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Enums\StatusDataPenduduk;
use App\Enums\StatusVerifikasiProfil;
use App\Models\JenisLayanan;
use App\Models\Penduduk;
use App\Models\ProfilWarga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesanIsianTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_menunjukkan_kolom_dan_alasannya(): void
    {
        $this->followingRedirects()
            ->from(route('daftar'))
            ->post(route('daftar.mulai'), [
                'nik' => '123',
                'nama' => '',
                'no_hp' => '999',
            ])
            ->assertOk()
            ->assertSee('Ada isian yang perlu diperbaiki.')
            ->assertSee('NIK harus 16 angka.')
            ->assertSee('Nama lengkap wajib diisi.')
            ->assertSee('Nomor HP harus diawali 08 dan berisi 10 sampai 14 angka.')
            ->assertSee('Centang persetujuan data pribadi.')
            ->assertSee('id="galat-nik"', false)
            ->assertSee('bidang-salah', false);
    }

    public function test_surat_menunjukkan_kolom_yang_kosong(): void
    {
        $warga = User::factory()->create([
            'peran' => Peran::Warga,
            'status_akun' => StatusAkun::Aktif,
            'nik' => '3404121101900002',
        ]);
        $penduduk = Penduduk::query()->create([
            'nik' => '3404121101900002',
            'nama' => 'Warga Pesan',
            'alamat_asli' => 'Dukuh Uji RT 001 RW 002',
            'status_data' => StatusDataPenduduk::Terverifikasi,
        ]);
        ProfilWarga::query()->create([
            'penduduk_id' => $penduduk->id,
            'user_id' => $warga->id,
            'status_verifikasi' => StatusVerifikasiProfil::Disetujui,
            'no_hp' => '081234567890',
        ]);
        $jenis = JenisLayanan::query()->create([
            'nama' => 'Surat Keterangan Usaha',
            'kode' => 'SKU',
            'butuh_pengantar_rt' => false,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => [
                ['nama' => 'ktp', 'label' => 'Foto KTP', 'wajib' => true],
            ],
            'skema_field_tambahan' => [],
            'template_surat' => 'surat.templates.sku',
            'aktif' => true,
        ]);

        $this->actingAs($warga)
            ->followingRedirects()
            ->from(route('warga.pengajuan.buat', $jenis))
            ->post(route('warga.pengajuan.simpan', $jenis), [
                'aksi' => 'kirim',
                'data' => [],
            ])
            ->assertOk()
            ->assertSee('Nama lengkap wajib diisi.')
            ->assertSee('Foto KTP wajib diunggah.')
            ->assertSee('id="galat-data-nama"', false);
    }
}
