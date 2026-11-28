<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Enums\StatusDataPenduduk;
use App\Enums\StatusPengajuan;
use App\Enums\StatusVerifikasiProfil;
use App\Models\Informasi;
use App\Models\JenisLayanan;
use App\Models\Notifikasi;
use App\Models\Penduduk;
use App\Models\Pengaduan;
use App\Models\Pengajuan;
use App\Models\ProfilWarga;
use App\Models\Surat;
use App\Models\User;
use App\Services\BerkasPrivat;
use App\Services\PenerbitSurat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;

class FaseTigaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_menyusun_layanan_dan_naskah_tidak_menjalankan_html(): void
    {
        $admin = User::factory()->create(['peran' => Peran::Admin]);

        $this->actingAs($admin)->post(route('admin.layanan.simpan'), [
            'nama' => 'Surat Uji',
            'kode' => 'uji',
            'estimasi_hari' => 2,
            'aktif' => '1',
            'naskah' => "Halo {{nama}}\n<script>alert(1)</script>",
            'field' => [
                ['nama' => 'keperluan', 'label' => 'Keperluan', 'wajib' => '1'],
            ],
        ])->assertRedirect(route('admin.layanan.index'));

        $jenis = JenisLayanan::query()->where('kode', 'UJI')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.templat', $jenis->kode))
            ->assertOk()
            ->assertSee('Sulastri')
            ->assertDontSee('<script>alert', false);

        $warga = User::factory()->create(['peran' => Peran::Warga]);
        $this->actingAs($warga)->post(route('admin.layanan.simpan'), [
            'nama' => 'Tidak boleh',
            'kode' => 'NO',
            'estimasi_hari' => 1,
            'naskah' => 'Teks',
        ])->assertForbidden();
    }

    public function test_admin_dapat_menonaktifkan_layanan_surat(): void
    {
        $admin = User::factory()->create(['peran' => Peran::Admin]);
        $jenis = JenisLayanan::query()->create([
            'nama' => 'Surat Uji Aktif',
            'kode' => 'UAKTIF',
            'butuh_pengantar_rt' => false,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => [],
            'skema_field_tambahan' => [],
            'template_surat' => 'surat.templates.dinamis',
            'naskah' => 'Surat untuk {{nama}}.',
            'aktif' => true,
        ]);
        $warga = User::factory()->create([
            'peran' => Peran::Warga,
            'status_akun' => StatusAkun::Aktif,
            'nik' => '3404121101900099',
        ]);
        $penduduk = Penduduk::query()->create([
            'nik' => '3404121101900099',
            'nama' => 'Warga Aktif',
            'alamat_asli' => 'Dukuh Uji',
            'status_data' => StatusDataPenduduk::Terverifikasi,
        ]);
        ProfilWarga::query()->create([
            'penduduk_id' => $penduduk->id,
            'user_id' => $warga->id,
            'status_verifikasi' => StatusVerifikasiProfil::Disetujui,
            'no_hp' => '081234567899',
        ]);

        $this->actingAs($admin)->patch(route('admin.layanan.status', $jenis))
            ->assertRedirect(route('admin.layanan.index'))
            ->assertSessionHas('status', 'Surat Uji Aktif dinonaktifkan. Warga tidak dapat mengajukan surat baru.');

        $this->assertFalse($jenis->fresh()->aktif);
        $this->flushSession();
        $this->actingAs($warga)->get(route('warga.pengajuan.layanan'))
            ->assertOk()
            ->assertDontSee('Surat Uji Aktif');
        $this->actingAs($warga)->post(route('warga.pengajuan.simpan', $jenis), ['aksi' => 'draf'])
            ->assertNotFound();

        $this->actingAs($admin)->patch(route('admin.layanan.status', $jenis))->assertRedirect();
        $this->assertTrue($jenis->fresh()->aktif);
        $this->actingAs($warga)->patch(route('admin.layanan.status', $jenis))->assertForbidden();
    }

    public function test_pengaduan_hanya_terlihat_pemilik_dan_petugas(): void
    {
        $pelapor = User::factory()->create(['peran' => Peran::Warga, 'name' => 'Pelapor']);
        $lain = User::factory()->create(['peran' => Peran::Warga, 'name' => 'Warga Lain']);
        $operator = User::factory()->create(['peran' => Peran::Operator]);

        $this->actingAs($pelapor)->post(route('pengaduan.simpan'), [
            'nama' => 'Pelapor',
            'no_hp' => '081234567890',
            'judul' => 'Lampu jalan mati',
            'isi' => 'Lampu di dukuh contoh padam.',
            'setuju' => '1',
        ])->assertRedirect();

        $this->actingAs($lain)->get(route('warga.pengaduan'))
            ->assertOk()
            ->assertDontSee('Lampu jalan mati');

        $this->actingAs($pelapor)->get(route('warga.pengaduan'))
            ->assertOk()
            ->assertSee('Lampu jalan mati');

        $pengaduan = Pengaduan::query()->firstOrFail();
        $this->actingAs($operator)->post(route('pengaduan.tanggapan', $pengaduan), [
            'status' => 'selesai',
            'tanggapan' => 'Sudah diteruskan ke petugas.',
        ])->assertRedirect();

        $this->actingAs($pelapor)->get(route('warga.pengaduan'))
            ->assertSee('Sudah diteruskan ke petugas.');
    }

    public function test_informasi_draf_tidak_tampil_dan_html_aman(): void
    {
        $admin = User::factory()->create(['peran' => Peran::Admin]);

        $this->actingAs($admin)->post(route('admin.informasi.simpan'), [
            'judul' => 'Pengumuman internal',
            'ringkasan' => 'Belum untuk umum',
            'isi' => '<b>rahasia</b>',
        ])->assertRedirect();

        $this->get(route('informasi.index'))
            ->assertOk()
            ->assertSee('Contoh informasi publik')
            ->assertDontSee('Pengumuman internal');

        $informasi = Informasi::query()->where('judul', 'Pengumuman internal')->firstOrFail();
        $this->get(route('informasi.lihat', $informasi))->assertNotFound();

        $this->actingAs($admin)->put(route('admin.informasi.perbarui', $informasi), [
            'judul' => 'Pengumuman internal',
            'ringkasan' => 'Sudah untuk umum',
            'isi' => '<b>rahasia</b>',
            'terbit' => '1',
        ])->assertRedirect();

        $this->get(route('informasi.lihat', $informasi))
            ->assertOk()
            ->assertDontSee('<b>rahasia', false);
    }

    public function test_tte_menolak_tanpa_gateway_dan_menyimpan_pdf_gateway(): void
    {
        $warga = User::factory()->create([
            'name' => 'Warga Tanda',
            'peran' => Peran::Warga,
            'status_akun' => StatusAkun::Aktif,
            'nik' => '3400000000000099',
        ]);
        $penduduk = Penduduk::query()->create([
            'nik' => '3400000000000099',
            'nama' => 'Warga Tanda',
            'alamat_asli' => 'Dukuh Contoh',
            'status_data' => StatusDataPenduduk::Terverifikasi,
        ]);
        ProfilWarga::query()->create([
            'penduduk_id' => $penduduk->id,
            'user_id' => $warga->id,
            'alamat_lengkap' => 'Dukuh Contoh',
            'status_verifikasi' => StatusVerifikasiProfil::Disetujui,
        ]);
        $jenis = JenisLayanan::query()->where('kode', 'NIKAH')->firstOrFail();
        $pengajuan = Pengajuan::query()->create([
            'kode_tiket' => 'LW-TTE',
            'user_id' => $warga->id,
            'jenis_layanan_id' => $jenis->id,
            'data_form' => [
                'status_perkawinan' => 'Belum kawin',
                'nama_pasangan' => 'Budi',
                'keperluan' => 'Nikah',
            ],
            'status' => StatusPengajuan::Diverifikasi,
            'status_pengantar_rt' => 'diunggah',
            'tanggal_ajuan' => now(),
        ]);
        Surat::query()->create([
            'pengajuan_id' => $pengajuan->id,
            'nomor_surat' => 'NIKAH/1/XI/2026',
            'tahun' => 2026,
            'nomor_urut' => 9,
            'kode_verifikasi' => str_repeat('d', 32),
        ]);
        $lurah = User::factory()->create(['peran' => Peran::Lurah]);
        Http::fake();

        $this->actingAs($lurah)->post(route('lurah.pengajuan.setujui', $pengajuan))->assertRedirect();
        Http::assertNothingSent();
        $this->assertSame(StatusPengajuan::Disetujui, $pengajuan->fresh()->status);
        $this->assertNull($pengajuan->fresh()->surat->file_pdf);

        $operator = User::factory()->create(['peran' => Peran::Operator]);
        $this->actingAs($operator)->post(route('operator.pengajuan.terbit', $pengajuan), [
            'berkas_tte' => UploadedFile::fake()->createWithContent('tte.pdf', '%PDF-TERSERTIFIKASI'),
        ])->assertRedirect();
        $pdf = app(BerkasPrivat::class)->baca($pengajuan->fresh()->surat->file_pdf);
        $this->assertStringStartsWith('%PDF-TERSERTIFIKASI', $pdf);
        $this->assertSame(StatusPengajuan::Selesai, $pengajuan->fresh()->status);
    }

    public function test_surat_selesai_mengirim_pdf_ke_whatsapp(): void
    {
        $pdf = '%PDF-1.4 surat-selesai';
        [$warga, $pengajuan] = $this->pengajuanDisetujui('081234567890');
        $this->siapkanWhatsapp();

        $this->actingAs(User::factory()->create(['peran' => Peran::Operator]))
            ->post(route('operator.pengajuan.terbit', $pengajuan), [
                'berkas_tte' => UploadedFile::fake()->createWithContent('tte.pdf', $pdf),
                'pesan' => 'Silakan disimpan.',
            ])->assertRedirect();

        Http::assertSent(fn ($permintaan) => $permintaan->url() === 'https://gateway.test/dokumen'
            && $permintaan->hasHeader('Authorization', 'Bearer token-uji')
            && $this->bagian($permintaan, 'tujuan') === '081234567890'
            && str_contains((string) $this->bagian($permintaan, 'pesan'), 'Silakan disimpan.')
            && $this->bagian($permintaan, 'berkas') === $pdf
            && $this->namaBagian($permintaan, 'berkas') === 'nikah-1-xi-2026.pdf');
        Http::assertNotSent(fn ($permintaan) => $permintaan->url() === 'https://gateway.test/kirim');
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $warga->id,
            'kanal' => 'whatsapp',
            'status_kirim' => 'terkirim',
        ]);
    }

    public function test_gagal_dokumen_tetap_mengirim_pesan_teks(): void
    {
        [$warga, $pengajuan] = $this->pengajuanDisetujui('081234567891');
        $this->siapkanWhatsapp(dokumenBerhasil: false);

        $this->actingAs(User::factory()->create(['peran' => Peran::Operator]))
            ->post(route('operator.pengajuan.terbit', $pengajuan), [
                'berkas_tte' => UploadedFile::fake()->createWithContent('tte.pdf', '%PDF-1.4'),
                'pesan' => 'Cek aplikasi.',
            ])->assertRedirect();

        Http::assertSent(fn ($permintaan) => $permintaan->url() === 'https://gateway.test/dokumen');
        Http::assertSent(fn ($permintaan) => $permintaan->url() === 'https://gateway.test/kirim'
            && str_contains((string) $this->bagian($permintaan, 'pesan'), 'Cek aplikasi.'));
        $this->assertSame(1, Notifikasi::query()->where('user_id', $warga->id)->count());
        $this->assertSame('terkirim', Notifikasi::query()->where('user_id', $warga->id)->value('status_kirim'));
    }

    public function test_format_selesai_mengirim_word_ke_whatsapp(): void
    {
        $warga = User::factory()->create([
            'peran' => Peran::Warga,
            'status_akun' => StatusAkun::Aktif,
            'no_hp' => '081234567892',
        ]);
        $jenis = JenisLayanan::query()->create([
            'nama' => 'Pernyataan Uji',
            'kode' => 'NYATAUJI',
            'daftar_syarat' => [],
            'skema_field_tambahan' => [],
            'template_surat' => 'surat.templates.dinamis',
            'pernyataan' => true,
        ]);
        $pengajuan = Pengajuan::query()->create([
            'kode_tiket' => 'LW-NYATA-WA',
            'user_id' => $warga->id,
            'jenis_layanan_id' => $jenis->id,
            'data_form' => [],
            'status' => StatusPengajuan::Diajukan,
            'status_pengantar_rt' => 'tidak_perlu',
            'tanggal_ajuan' => now(),
        ]);
        $this->siapkanWhatsapp();
        $berkas = "PK\x03\x04format-uji";

        app(PenerbitSurat::class)->kirimFormat(
            $pengajuan,
            User::factory()->create(['peran' => Peran::Operator]),
            $berkas,
            'Cetak lalu tanda tangani.',
        );

        Http::assertSent(fn ($permintaan) => $permintaan->url() === 'https://gateway.test/dokumen'
            && $this->bagian($permintaan, 'berkas') === $berkas
            && $this->namaBagian($permintaan, 'berkas') === 'lw-nyata-wa.docx'
            && str_contains((string) $this->bagian($permintaan, 'pesan'), 'Cetak lalu tanda tangani.'));
        $this->assertSame(StatusPengajuan::Selesai, $pengajuan->fresh()->status);
    }

    /**
     * @return array{0: User, 1: Pengajuan}
     */
    private function pengajuanDisetujui(string $noHp): array
    {
        $warga = User::factory()->create([
            'peran' => Peran::Warga,
            'status_akun' => StatusAkun::Aktif,
            'nik' => '3400000000000'.substr($noHp, -3),
            'no_hp' => $noHp,
        ]);
        $jenis = JenisLayanan::query()->where('kode', 'NIKAH')->firstOrFail();
        $pengajuan = Pengajuan::query()->create([
            'kode_tiket' => 'LW-WA-'.$noHp,
            'user_id' => $warga->id,
            'jenis_layanan_id' => $jenis->id,
            'data_form' => ['keperluan' => 'Nikah'],
            'status' => StatusPengajuan::Disetujui,
            'status_pengantar_rt' => 'diunggah',
            'tanggal_ajuan' => now(),
        ]);
        Surat::query()->create([
            'pengajuan_id' => $pengajuan->id,
            'nomor_surat' => 'NIKAH/1/XI/2026',
            'tahun' => 2026,
            'nomor_urut' => 40,
            'kode_verifikasi' => str_repeat('e', 32),
        ]);

        return [$warga, $pengajuan];
    }

    private function siapkanWhatsapp(bool $dokumenBerhasil = true): void
    {
        Http::fake([
            'https://gateway.test/dokumen' => Http::response(['success' => $dokumenBerhasil], $dokumenBerhasil ? 200 : 503),
            'https://gateway.test/kirim' => Http::response(['success' => true]),
        ]);
        config([
            'surat.notifikasi.kanal' => 'whatsapp',
            'surat.notifikasi.whatsapp_url' => 'https://gateway.test/kirim',
            'surat.notifikasi.whatsapp_dokumen_url' => 'https://gateway.test/dokumen',
            'surat.notifikasi.whatsapp_token' => 'token-uji',
            'surat.notifikasi.nomor_whatsapp' => '0881080712007',
        ]);
    }

    private function bagian(Request $permintaan, string $nama): ?string
    {
        $data = $permintaan->data();
        $isi = is_string($data[$nama] ?? null)
            ? $data[$nama]
            : (collect($data)->firstWhere('name', $nama)['contents'] ?? null);

        if ($isi instanceof StreamInterface) {
            $isi->rewind();

            return $isi->getContents();
        }

        return is_string($isi) ? $isi : null;
    }

    private function namaBagian(Request $permintaan, string $nama): ?string
    {
        $berkas = collect($permintaan->data())->firstWhere('name', $nama);

        return is_array($berkas) ? ($berkas['filename'] ?? null) : null;
    }
}
