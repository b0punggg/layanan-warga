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
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class UnduhWordTest extends TestCase
{
    use RefreshDatabase;

    public function test_surat_selesai_bisa_diunduh_word_f4_tanpa_kode_keaslian(): void
    {
        $pemilik = $this->warga('3400000000000092', 'Sulastri');
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
        $surat = $this->surat($pemilik, $jenis, '400.10.5/151', [
            'jenis_usaha' => 'Warung makan',
            'alamat_usaha' => 'Sorobayan',
        ]);

        $operator = User::factory()->create(['peran' => Peran::Operator]);
        $respon = $this->actingAs($operator)->get(route('operator.pengajuan.naskah.word', $surat->pengajuan));
        $respon->assertOk();
        $respon->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertStringStartsWith('PK', $respon->getContent());

        $xml = $this->dokumen($respon->getContent());
        $this->assertStringContainsString('SURAT KETERANGAN USAHA', $xml);
        $this->assertStringContainsString('400.10.5/151', $xml);
        $this->assertStringContainsString('Warung makan', $xml);
        $this->assertStringContainsString('w:w="12242"', $xml);
        $this->assertStringContainsString('w:h="18722"', $xml);
        $this->assertStringContainsString('[onshow.ttd_19254;ope=changepic;noerr]', $xml);
        $this->assertStringContainsString('Drs. Anang Zamroni, M.S.I.', $xml);
        $this->assertStringNotContainsString('Periksa keaslian', $xml);

        $this->actingAs($pemilik)->get(route('operator.pengajuan.naskah.word', $surat->pengajuan))->assertForbidden();

        $surat->pengajuan->update(['status' => StatusPengajuan::Selesai]);
        $this->actingAs($operator)->get(route('operator.pengajuan.naskah.word', $surat->pengajuan))->assertNotFound();
    }

    public function test_naskah_dinamis_mengisi_data_warga(): void
    {
        $pemilik = $this->warga('3400000000000094', 'Budi Santoso');
        $jenis = JenisLayanan::query()->where('kode', 'NIKAH')->firstOrFail();
        $jenis->update([
            'naskah' => 'Yang bertanda tangan menerangkan bahwa {{ nama }} dengan NIK {{ nik }} berdomisili di kalurahan.',
        ]);
        $surat = $this->surat($pemilik, $jenis, 'NIKAH/1/XI/2026', []);

        $operator = User::factory()->create(['peran' => Peran::Operator]);
        $xml = $this->dokumen($this->actingAs($operator)->get(route('operator.pengajuan.naskah.word', $surat->pengajuan))->assertOk()->getContent());
        $this->assertStringContainsString('berdomisili di kalurahan', $xml);
        $this->assertStringContainsString('Budi Santoso', $xml);
        $this->assertStringContainsString('3400000000000094', $xml);
        $this->assertStringNotContainsString('{{', $xml);
    }

    private function warga(string $nik, string $nama): User
    {
        $user = User::factory()->create([
            'name' => $nama,
            'peran' => Peran::Warga,
            'status_akun' => StatusAkun::Aktif,
            'nik' => $nik,
        ]);
        $penduduk = Penduduk::query()->create([
            'nik' => $nik,
            'nama' => $nama,
            'alamat_asli' => 'Dukuh Contoh',
            'status_data' => StatusDataPenduduk::Terverifikasi,
        ]);
        ProfilWarga::query()->create([
            'penduduk_id' => $penduduk->id,
            'user_id' => $user->id,
            'tempat_lahir' => 'Sleman',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Perempuan',
            'agama' => 'Islam',
            'pekerjaan' => 'Wiraswasta',
            'alamat_lengkap' => 'Dukuh Contoh RT 001 RW 002',
            'status_verifikasi' => StatusVerifikasiProfil::Disetujui,
        ]);

        return $user;
    }

    /**
     * @param  array<string, string>  $form
     */
    private function surat(User $pemilik, JenisLayanan $jenis, string $nomor, array $form): Surat
    {
        $pengajuan = Pengajuan::query()->create([
            'kode_tiket' => 'LW-'.$jenis->kode.'-'.$pemilik->id,
            'user_id' => $pemilik->id,
            'jenis_layanan_id' => $jenis->id,
            'data_form' => $form,
            'status' => StatusPengajuan::Disetujui,
            'tanggal_ajuan' => now(),
        ]);

        return Surat::query()->create([
            'pengajuan_id' => $pengajuan->id,
            'nomor_surat' => $nomor,
            'tahun' => 2026,
            'nomor_urut' => $pemilik->id,
            'tanggal_terbit' => '2026-11-24',
            'kode_verifikasi' => hash('sha256', $nomor),
        ]);
    }

    private function dokumen(string $docx): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($path, $docx);
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        return $xml;
    }
}
