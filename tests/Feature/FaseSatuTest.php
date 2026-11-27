<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Enums\StatusDataPenduduk;
use App\Enums\StatusPengajuan;
use App\Enums\StatusVerifikasiProfil;
use App\Models\JenisLayanan;
use App\Models\Penduduk;
use App\Models\Pengajuan;
use App\Models\ProfilWarga;
use App\Models\User;
use App\Services\BerkasPrivat;
use App\Services\PembacaKtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaseSatuTest extends TestCase
{
    use RefreshDatabase;

    public function test_impor_memperbarui_tanpa_menggandakan_nik(): void
    {
        $admin = User::factory()->create(['peran' => Peran::Admin]);
        Penduduk::query()->create([
            'nik' => '3400000000000002',
            'nama' => 'Nama Lama',
            'alamat_asli' => 'Lama',
            'status_data' => StatusDataPenduduk::Menunggu,
        ]);

        $berkas = UploadedFile::fake()->createWithContent('penduduk.csv', implode("\n", [
            'nama,nik,alamat',
            'Nama Baru,3400000000000002,Dukuh Contoh RT 1 RW 2',
            'Kosong,123,Alamat',
        ]));

        $pratinjau = $this->actingAs($admin)->post(route('admin.impor.pratinjau'), ['berkas' => $berkas]);
        $pratinjau->assertOk()->assertSee('diperbarui 1', false)->assertSee('gagal 1', false);
        preg_match('/name="token" value="([^"]+)"/', $pratinjau->getContent(), $cocok);

        $this->actingAs($admin)->post(route('admin.impor.simpan'), ['token' => $cocok[1]])
            ->assertRedirect(route('admin.impor.buat'));

        $this->assertSame(1, Penduduk::query()->where('nik', '3400000000000002')->count());
        $this->assertDatabaseHas('penduduk', [
            'nik' => '3400000000000002',
            'nama' => 'Nama Baru',
            'dukuh' => 'Dukuh Contoh',
            'rt' => '001',
            'rw' => '002',
        ]);
        $this->assertDatabaseMissing('penduduk', ['nik' => '123']);
    }

    public function test_alur_surat_dari_daftar_sampai_pdf(): void
    {
        Storage::fake('berkas');
        Penduduk::query()->create([
            'nik' => '3400000000000088',
            'nama' => 'Warga Uji',
            'alamat_asli' => 'Dukuh Uji RT 003 RW 004',
            'status_data' => StatusDataPenduduk::Terverifikasi,
        ]);

        $this->post(route('daftar.mulai'), [
            'nik' => '3400000000000088',
            'nama' => 'Nama Lain',
            'no_hp' => '081111111111',
            'setuju_data' => '1',
        ])->assertRedirect(route('daftar.otp'));
        $this->assertStringNotContainsString('Dukuh Uji', (string) session('status'));

        $this->post(route('daftar.mulai'), [
            'nik' => '3400000000000077',
            'nama' => 'Warga Baru',
            'no_hp' => '081222222222',
            'setuju_data' => '1',
        ])->assertSessionHas('otp_pengembangan');

        $this->post(route('daftar.otp.periksa'), ['kode' => session('otp_pengembangan')])
            ->assertRedirect(route('daftar.profil'));

        $this->post(route('daftar.profil.simpan'), [
            'password' => 'kata-sandi-aman',
            'password_confirmation' => 'kata-sandi-aman',
            'tempat_lahir' => 'Sleman',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Perempuan',
            'agama' => 'Islam',
            'status_kawin' => 'Kawin',
            'pekerjaan' => 'Wiraswasta',
            'dukuh' => 'Dukuh Baru',
            'rt' => '001',
            'rw' => '002',
            'alamat_lengkap' => 'Dukuh Baru RT 001 RW 002',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'foto_selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect(route('masuk'));

        $warga = User::query()->where('nik', '3400000000000077')->firstOrFail();
        $this->actingAs($warga)->get(route('warga.pengajuan.layanan'))->assertForbidden();

        $operator = User::factory()->create(['peran' => Peran::Operator]);
        $this->actingAs($operator)->post(route('operator.akun.setujui', $warga->profilWarga))
            ->assertRedirect(route('operator.akun.index'));
        $this->assertNull($warga->profilWarga->fresh()->foto_selfie_path);

        $jenis = $this->jenisSku();
        $this->actingAs($warga->fresh())->post(route('warga.pengajuan.simpan', $jenis), [
            'aksi' => 'kirim',
            'data' => [
                'nama' => 'Penguji Satu',
                'nik' => '3400000000000077',
                'tempat_lahir' => 'Sleman',
                'tanggal_lahir' => '1 Januari 1990',
                'jenis_kelamin' => 'Perempuan',
                'warga_negara' => 'Indonesia',
                'agama' => 'Islam',
                'pekerjaan' => 'Wiraswasta',
                'alamat' => 'Dukuh Baru',
                'nama_usaha' => 'Warung Uji',
                'jenis_usaha' => 'Dagang',
                'alamat_usaha' => 'Dukuh Baru',
            ],
            'berkas' => [
                'ktp' => UploadedFile::fake()->create('ktp.pdf', 20, 'application/pdf'),
                'kk' => UploadedFile::fake()->create('kk.pdf', 20, 'application/pdf'),
            ],
            'pengantar' => UploadedFile::fake()->image('pengantar.jpg'),
        ])->assertRedirect();

        $pengajuan = Pengajuan::query()->firstOrFail();
        $lain = User::factory()->create(['peran' => Peran::Warga]);
        $this->actingAs($lain)->get(route('warga.pengajuan.lihat', $pengajuan))->assertForbidden();

        $this->actingAs($operator)->post(route('operator.pengajuan.kirim', $pengajuan))->assertRedirect();
        $this->assertSame(1, $pengajuan->fresh()->surat->nomor_urut);

        $lurah = User::factory()->create(['peran' => Peran::Lurah]);
        $this->actingAs($lurah)->post(route('lurah.pengajuan.setujui', $pengajuan))->assertRedirect();
        $this->assertSame('disetujui', $pengajuan->fresh()->status->value);
        $this->assertNull($pengajuan->fresh()->surat->file_pdf);

        $naskah = $this->actingAs($operator)->get(route('operator.pengajuan.naskah', $pengajuan));
        $naskah->assertOk();
        $this->assertStringStartsWith('%PDF', $naskah->getContent());
        $this->assertStringContainsString('936', $naskah->getContent());
        $this->assertStringNotContainsString('Periksa keaslian', $naskah->getContent());

        $this->actingAs($operator)->post(route('operator.pengajuan.terbit', $pengajuan), [
            'berkas_tte' => UploadedFile::fake()->createWithContent('tte.pdf', '%PDF-1.4 tersertifikasi'),
        ])->assertRedirect();

        $tersimpan = app(BerkasPrivat::class)->baca($pengajuan->fresh()->surat->file_pdf);
        $this->assertStringStartsWith('%PDF-1.4 tersertifikasi', $tersimpan);
        $this->assertSame('selesai', $pengajuan->fresh()->status->value);
    }

    public function test_operator_dapat_mengubah_nomor_sebelum_ke_lurah(): void
    {
        $jenis = JenisLayanan::query()->create([
            'nama' => 'Surat Keterangan Usaha',
            'kode' => 'SKU',
            'butuh_pengantar_rt' => false,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => [],
            'skema_field_tambahan' => [],
            'format_nomor' => '400.10.5/{nomor}',
            'template_surat' => 'surat.templates.sku',
            'aktif' => true,
        ]);
        $warga = User::factory()->create(['peran' => Peran::Warga]);
        $operator = User::factory()->create(['peran' => Peran::Operator]);
        $pengajuan = Pengajuan::query()->create([
            'kode_tiket' => 'LW-NOMOR-1',
            'user_id' => $warga->id,
            'jenis_layanan_id' => $jenis->id,
            'data_form' => [],
            'status' => StatusPengajuan::Diajukan,
            'status_pengantar_rt' => 'tidak_perlu',
        ]);

        $this->actingAs($operator)->get(route('operator.pengajuan.lihat', $pengajuan))
            ->assertOk()
            ->assertSee('value="400.10.5/001"', false);

        $this->actingAs($operator)->post(route('operator.pengajuan.kirim', $pengajuan), [
            'nomor_surat' => '400.10.5/151',
        ])->assertRedirect();

        $surat = $pengajuan->fresh()->surat;
        $this->assertSame('400.10.5/151', $surat->nomor_surat);
        $this->assertSame(151, $surat->nomor_urut);

        $kedua = Pengajuan::query()->create([
            'kode_tiket' => 'LW-NOMOR-2',
            'user_id' => $warga->id,
            'jenis_layanan_id' => $jenis->id,
            'data_form' => [],
            'status' => StatusPengajuan::Diajukan,
            'status_pengantar_rt' => 'tidak_perlu',
        ]);

        $this->actingAs($operator)->post(route('operator.pengajuan.kirim', $kedua), [
            'nomor_surat' => '400.10.5/151',
        ])->assertSessionHasErrors('aksi');
        $this->assertNull($kedua->fresh()->surat);
    }

    public function test_daftar_singkat_mengisi_dari_nik_dan_surat_melengkapi_profil(): void
    {
        Storage::fake('berkas');
        Penduduk::query()->create([
            'nik' => '3404125101900001',
            'nama' => 'Sulastri Uji',
            'alamat_asli' => 'Dukuh Sindu RT 001 RW 002',
            'status_data' => StatusDataPenduduk::Terverifikasi,
        ]);

        $this->post(route('daftar.mulai'), [
            'nik' => '3404125101900001',
            'nama' => 'Sulastri Uji',
            'no_hp' => '081333333333',
            'setuju_data' => '1',
        ])->assertRedirect(route('daftar.otp'));

        $this->post(route('daftar.otp.periksa'), ['kode' => session('otp_pengembangan')])
            ->assertRedirect(route('daftar.profil'));

        $this->postJson(route('daftar.profil.baca'), [
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
        ])->assertOk()
            ->assertJsonPath('isian', [])
            ->assertJsonFragment(['peringatan' => 'NIK pada foto belum terbaca. Periksa isian yang sudah terisi, lalu kirim.']);

        $this->get(route('daftar.profil'))
            ->assertOk()
            ->assertSee('Dukuh Sindu RT 001 RW 002')
            ->assertSee('1990-01-11')
            ->assertDontSee('Nomor KK');

        $this->post(route('daftar.profil.simpan'), [
            'password' => 'kata-sandi-aman',
            'password_confirmation' => 'kata-sandi-aman',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
        ])->assertRedirect(route('masuk'))
            ->assertSessionHas('status', 'Pendaftaran tersimpan. Akun menunggu pemeriksaan operator. Kabar persetujuan dikirim ke WhatsApp Anda.');

        $warga = User::query()->where('nik', '3404125101900001')->firstOrFail();
        $this->assertSame(StatusAkun::Menunggu, $warga->status_akun);
        $profil = $warga->profilWarga;
        $this->assertSame(StatusVerifikasiProfil::Menunggu, $profil->status_verifikasi);
        $this->assertSame('Dukuh Sindu RT 001 RW 002', $profil->alamat_lengkap);
        $this->assertSame('Dukuh Sindu', $profil->dukuh);
        $this->assertSame('001', $profil->rt);
        $this->assertSame('002', $profil->rw);
        $this->assertSame('1990-01-11', $profil->tanggal_lahir->toDateString());
        $this->assertSame('Perempuan', $profil->jenis_kelamin);
        $this->assertNull($profil->tempat_lahir);
        $this->assertNull($profil->agama);
        $this->assertNull($profil->foto_selfie_path);
        $this->assertNotNull($profil->foto_ktp_path);

        $operator = User::factory()->create(['peran' => Peran::Operator]);
        $this->actingAs($operator)->post(route('operator.akun.setujui', $profil))->assertRedirect();
        $this->assertSame(StatusAkun::Aktif, $warga->fresh()->status_akun);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $warga->id,
            'tujuan' => '081333333333',
            'isi' => 'Pendaftaran Layanan Surat Kalurahan Sindumartani disetujui. Anda sudah dapat masuk dan mengajukan surat.',
        ]);

        $jenis = JenisLayanan::query()->create([
            'nama' => 'Surat Keterangan Usaha',
            'kode' => 'SKU',
            'butuh_pengantar_rt' => false,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => [],
            'skema_field_tambahan' => [],
            'template_surat' => 'surat.templates.sku',
            'aktif' => true,
        ]);

        $this->actingAs($warga->fresh())->post(route('warga.pengajuan.simpan', $jenis), [
            'aksi' => 'draf',
            'data' => [
                'tempat_lahir' => 'Sleman',
                'agama' => 'Islam',
                'tanggal_lahir' => '11 Januari 1990',
                'alamat' => 'Padukuhan Baru RT 005 RW 006',
            ],
        ])->assertRedirect();

        $profil->refresh();
        $this->assertSame('Sleman', $profil->tempat_lahir);
        $this->assertSame('Islam', $profil->agama);
        $this->assertSame('1990-01-11', $profil->tanggal_lahir->toDateString());
        $this->assertSame('Padukuhan Baru RT 005 RW 006', $profil->alamat_lengkap);
        $this->assertSame('Dukuh Sindu', $profil->dukuh);
        $this->assertSame('001', $profil->rt);
        $this->assertSame('002', $profil->rw);
    }

    public function test_nik_cocok_dan_foto_ktp_sama_langsung_aktif(): void
    {
        Storage::fake('berkas');
        Penduduk::query()->create([
            'nik' => '3404125101900002',
            'nama' => 'Warga Langsung',
            'alamat_asli' => 'Dukuh Langsung RT 001 RW 002',
            'status_data' => StatusDataPenduduk::Terverifikasi,
        ]);

        $this->post(route('daftar.mulai'), [
            'nik' => '3404125101900002',
            'nama' => 'Warga Langsung',
            'no_hp' => '081444444444',
            'setuju_data' => '1',
        ]);
        $this->post(route('daftar.otp.periksa'), ['kode' => session('otp_pengembangan')]);

        $this->mock(PembacaKtp::class, function ($mock): void {
            $mock->shouldReceive('teks')->once()->andReturn('NIK 3404125101900002');
            $mock->shouldReceive('uraikan')->once()->andReturn(['nik' => '3404125101900002']);
        });

        $this->post(route('daftar.profil.simpan'), [
            'password' => 'kata-sandi-aman',
            'password_confirmation' => 'kata-sandi-aman',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
        ])->assertRedirect(route('masuk'))
            ->assertSessionHas('status', 'Pendaftaran berhasil. Akun sudah aktif. Silakan masuk.');

        $warga = User::query()->where('nik', '3404125101900002')->firstOrFail();
        $this->assertSame(StatusAkun::Aktif, $warga->status_akun);
        $this->assertSame(StatusVerifikasiProfil::Disetujui, $warga->profilWarga->status_verifikasi);
        $this->assertNotNull($warga->profilWarga->tanggal_verifikasi);
        $this->assertNull($warga->profilWarga->diverifikasi_oleh);
        $this->actingAs($warga)->get(route('warga.pengajuan.layanan'))->assertOk();
    }

    public function test_foto_ktp_berbeda_tetap_menunggu_operator(): void
    {
        Storage::fake('berkas');
        Penduduk::query()->create([
            'nik' => '3404125101900003',
            'nama' => 'Warga Foto',
            'alamat_asli' => 'Dukuh Foto',
            'status_data' => StatusDataPenduduk::Terverifikasi,
        ]);

        $this->post(route('daftar.mulai'), [
            'nik' => '3404125101900003',
            'nama' => 'Warga Foto',
            'no_hp' => '081444444445',
            'setuju_data' => '1',
        ]);
        $this->post(route('daftar.otp.periksa'), ['kode' => session('otp_pengembangan')]);

        $this->mock(PembacaKtp::class, function ($mock): void {
            $mock->shouldReceive('teks')->once()->andReturn('NIK 3404125101909999');
            $mock->shouldReceive('uraikan')->once()->andReturn(['nik' => '3404125101909999']);
        });

        $this->post(route('daftar.profil.simpan'), [
            'password' => 'kata-sandi-aman',
            'password_confirmation' => 'kata-sandi-aman',
            'alamat_lengkap' => 'Dukuh Foto',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
        ])->assertSessionHas('status', 'Pendaftaran tersimpan. Akun menunggu pemeriksaan operator. Kabar persetujuan dikirim ke WhatsApp Anda.');

        $warga = User::query()->where('nik', '3404125101900003')->firstOrFail();
        $this->assertSame(StatusAkun::Menunggu, $warga->status_akun);
    }

    public function test_perbaikan_dan_penolakan_dikirim_ke_pendaftar(): void
    {
        $operator = User::factory()->create(['peran' => Peran::Operator]);
        $perbaikan = $this->profilMenunggu('3400000000000091', '081555555551');
        $tolak = $this->profilMenunggu('3400000000000092', '081555555552');

        $this->actingAs($operator)->post(route('operator.akun.perbaikan', $perbaikan), [
            'catatan' => 'Unggah ulang foto KTP.',
        ])->assertRedirect();

        $this->actingAs($perbaikan->user)->get(route('warga.menunggu'))
            ->assertOk()
            ->assertSee('Operator meminta perbaikan data')
            ->assertSee('Unggah ulang foto KTP.');

        $this->actingAs($operator)->post(route('operator.akun.tolak', $tolak), [
            'catatan' => 'Bukan warga kalurahan.',
        ])->assertRedirect(route('operator.akun.index'));

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $perbaikan->user_id,
            'isi' => 'Pendaftaran Layanan Surat perlu diperbaiki. Unggah ulang foto KTP. Masuk ke akun Anda lalu perbarui profil.',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $tolak->user_id,
            'isi' => 'Pendaftaran Layanan Surat ditolak. Catatan operator: Bukan warga kalurahan.',
        ]);
        $this->assertSame(StatusAkun::Ditolak, $tolak->user->fresh()->status_akun);
    }

    private function profilMenunggu(string $nik, string $hp): ProfilWarga
    {
        $penduduk = Penduduk::query()->create([
            'nik' => $nik,
            'nama' => 'Warga Kabar',
            'alamat_asli' => 'Dukuh Kabar',
            'status_data' => StatusDataPenduduk::Terverifikasi,
        ]);
        $user = User::factory()->create([
            'name' => 'Warga Kabar',
            'nik' => $nik,
            'no_hp' => $hp,
            'peran' => Peran::Warga,
            'status_akun' => StatusAkun::Menunggu,
        ]);

        return ProfilWarga::query()->create([
            'penduduk_id' => $penduduk->id,
            'user_id' => $user->id,
            'no_hp' => $hp,
            'alamat_lengkap' => 'Dukuh Kabar',
            'status_verifikasi' => StatusVerifikasiProfil::Menunggu,
        ]);
    }

    private function jenisSku(): JenisLayanan
    {
        return JenisLayanan::query()->create([
            'nama' => 'Surat Keterangan Usaha',
            'kode' => 'SKU',
            'butuh_pengantar_rt' => true,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => [
                ['nama' => 'ktp', 'label' => 'Foto KTP', 'wajib' => true],
                ['nama' => 'kk', 'label' => 'Foto Kartu Keluarga', 'wajib' => true],
            ],
            'skema_field_tambahan' => [
                ['nama' => 'nama_usaha', 'label' => 'Nama usaha', 'wajib' => true],
                ['nama' => 'jenis_usaha', 'label' => 'Jenis usaha', 'wajib' => true],
                ['nama' => 'alamat_usaha', 'label' => 'Alamat usaha', 'wajib' => true],
            ],
            'template_surat' => 'surat.templates.sku',
            'estimasi_hari' => 3,
            'aktif' => true,
        ]);
    }
}
