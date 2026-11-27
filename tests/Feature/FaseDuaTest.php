<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Enums\StatusDataPenduduk;
use App\Enums\StatusPengajuan;
use App\Enums\StatusVerifikasiProfil;
use App\Models\JenisLayanan;
use App\Models\Notifikasi;
use App\Models\Penduduk;
use App\Models\Pengajuan;
use App\Models\ProfilWarga;
use App\Models\Surat;
use App\Models\User;
use App\Services\PengirimPesan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FaseDuaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengantar_online_menunggu_rt_sebelum_nomor(): void
    {
        [$warga, $jenis] = $this->wargaSiap();
        $rt = User::factory()->create([
            'peran' => Peran::RtRw,
            'dukuh' => 'Contoh',
            'rt' => '001',
            'rw' => '002',
        ]);
        $rtLain = User::factory()->create([
            'peran' => Peran::RtRw,
            'dukuh' => 'Contoh',
            'rt' => '009',
            'rw' => '002',
        ]);

        $this->actingAs($warga)->post(route('warga.pengajuan.simpan', $jenis), [
            'aksi' => 'kirim',
            'data' => [
                'nama' => 'Warga Uji',
                'nik' => '3400000000000091',
                'tempat_lahir' => 'Sleman',
                'tanggal_lahir' => '1 Januari 1990',
                'pekerjaan' => 'Wiraswasta',
                'alamat' => 'Dukuh Contoh RT 001 RW 002',
                'jumlah_penghasilan' => '1500000',
                'keperluan' => 'Beasiswa',
            ],
            'berkas' => [
                'ktp' => UploadedFile::fake()->create('ktp.pdf', 20, 'application/pdf'),
                'kk' => UploadedFile::fake()->create('kk.pdf', 20, 'application/pdf'),
            ],
        ])->assertRedirect();

        $pengajuan = Pengajuan::query()->firstOrFail();
        $this->assertSame('menunggu_rt', $pengajuan->status_pengantar_rt->value);
        $this->assertDatabaseHas('notifikasi', ['user_id' => $warga->id]);

        $operator = User::factory()->create(['peran' => Peran::Operator]);
        $this->actingAs($operator)->post(route('operator.pengajuan.kirim', $pengajuan))->assertSessionHasErrors('aksi');
        $this->actingAs($rtLain)->get(route('rt.pengajuan.lihat', $pengajuan))->assertForbidden();
        $this->actingAs($rt)->post(route('rt.pengajuan.setujui', $pengajuan))->assertRedirect();
        $this->actingAs($operator)->post(route('operator.pengajuan.kirim', $pengajuan))->assertRedirect();
        $this->assertNotNull($pengajuan->fresh()->surat);
    }

    public function test_cek_surat_menyembunyikan_nik_dan_menolak_kode_asing(): void
    {
        [$warga] = $this->wargaSiap();
        $jenis = JenisLayanan::query()->where('kode', 'SKCK')->firstOrFail();
        $pengajuan = Pengajuan::query()->create([
            'kode_tiket' => 'LW-UJI-SKCK',
            'user_id' => $warga->id,
            'jenis_layanan_id' => $jenis->id,
            'data_form' => ['keperluan' => 'Melamar kerja'],
            'status' => StatusPengajuan::Diverifikasi,
            'status_pengantar_rt' => 'tidak_perlu',
            'tanggal_ajuan' => now(),
        ]);
        Surat::query()->create([
            'pengajuan_id' => $pengajuan->id,
            'nomor_surat' => 'SKCK/1/XI/2026',
            'tahun' => 2026,
            'nomor_urut' => 1,
            'kode_verifikasi' => str_repeat('a', 32),
        ]);

        $lurah = User::factory()->create(['peran' => Peran::Lurah]);
        $this->actingAs($lurah)->post(route('lurah.pengajuan.setujui', $pengajuan))->assertRedirect();
        $this->get(route('cek.surat', str_repeat('a', 32)))
            ->assertOk()
            ->assertSee('tidak ditemukan');

        $operator = User::factory()->create(['peran' => Peran::Operator]);
        $this->actingAs($operator)->post(route('operator.pengajuan.terbit', $pengajuan), [
            'berkas_tte' => UploadedFile::fake()->createWithContent('tte.pdf', '%PDF-1.4'),
        ])->assertRedirect();

        $this->get(route('cek.surat', str_repeat('a', 32)))
            ->assertOk()
            ->assertSee('SKCK/1/XI/2026')
            ->assertSee($warga->name)
            ->assertDontSee($warga->nik);

        $this->get(route('cek.surat', str_repeat('b', 32)))
            ->assertOk()
            ->assertSee('tidak ditemukan');

        $this->actingAs($lurah)->get(route('laporan.index', ['tahun' => now()->year]))
            ->assertOk()
            ->assertSee('Pengantar SKCK');
        $this->actingAs($warga)->get(route('laporan.index'))->assertForbidden();
        $this->actingAs($lurah)->get(route('laporan.excel', ['tahun' => now()->year]))->assertOk();
        auth()->logout();
        $this->get(route('laporan.excel', ['tahun' => now()->year]))->assertRedirect();
        $this->actingAs($lurah)->get(route('laporan.pdf', ['tahun' => now()->year]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_otp_tidak_disimpan_dan_whatsapp_mengirim_json(): void
    {
        $this->post(route('daftar.mulai'), [
            'nik' => '3400000000000088',
            'nama' => 'Budi Uji',
            'no_hp' => '081234567899',
            'setuju_data' => '1',
        ])->assertRedirect(route('daftar.otp'));

        $kode = session('otp_pengembangan');
        $this->assertNotNull($kode);
        $this->assertStringNotContainsString($kode, (string) Notifikasi::query()->value('isi'));

        Http::fake(['https://gateway.test/*' => Http::response(['ok' => true])]);
        config([
            'surat.notifikasi.kanal' => 'whatsapp',
            'surat.notifikasi.whatsapp_url' => 'https://gateway.test/kirim',
            'surat.notifikasi.nomor_whatsapp' => '0881080712007',
        ]);

        $user = User::factory()->create(['no_hp' => '081111111111']);
        app(PengirimPesan::class)->keAkun($user, 'Status berubah.');

        Http::assertSent(function ($permintaan) {
            return $permintaan->url() === 'https://gateway.test/kirim'
                && $permintaan['pengirim'] === '0881080712007'
                && $permintaan['tujuan'] === '081111111111'
                && $permintaan['pesan'] === 'Status berubah.';
        });
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $user->id,
            'kanal' => 'whatsapp',
            'status_kirim' => 'terkirim',
        ]);
    }

    /**
     * @return array{0: User, 1: JenisLayanan}
     */
    private function wargaSiap(): array
    {
        $warga = User::factory()->create([
            'name' => 'Warga Uji',
            'peran' => Peran::Warga,
            'status_akun' => StatusAkun::Aktif,
            'nik' => '3400000000000091',
            'no_hp' => '081234567891',
        ]);
        $penduduk = Penduduk::query()->create([
            'nik' => '3400000000000091',
            'nama' => 'Warga Uji',
            'alamat_asli' => 'Dukuh Contoh RT 001 RW 002',
            'status_data' => StatusDataPenduduk::Terverifikasi,
        ]);
        ProfilWarga::query()->create([
            'penduduk_id' => $penduduk->id,
            'user_id' => $warga->id,
            'dukuh' => 'Contoh',
            'rt' => '001',
            'rw' => '002',
            'alamat_lengkap' => 'Dukuh Contoh RT 001 RW 002',
            'status_verifikasi' => StatusVerifikasiProfil::Disetujui,
        ]);

        return [$warga, JenisLayanan::query()->where('kode', 'PHSL')->firstOrFail()];
    }
}
