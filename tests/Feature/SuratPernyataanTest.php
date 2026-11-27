<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Enums\StatusPengajuan;
use App\Models\JenisLayanan;
use App\Models\Notifikasi;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class SuratPernyataanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_mengunggah_template_dan_operator_mengirim_pesan(): void
    {
        Storage::fake('berkas');
        $admin = User::factory()->create(['peran' => Peran::Admin]);
        $berkas = UploadedFile::fake()->createWithContent('pernyataan.docx', $this->docx('Pernyataan {{nama}} untuk {{keperluan}}.'));

        $this->actingAs($admin)->post(route('admin.layanan.simpan'), [
            'nama' => 'Surat Pernyataan',
            'kode' => 'NYATA',
            'estimasi_hari' => 1,
            'aktif' => '1',
            'pernyataan' => '1',
            'template_word' => $berkas,
        ])->assertRedirect(route('admin.layanan.index'));

        $jenis = JenisLayanan::query()->where('kode', 'NYATA')->firstOrFail();
        $this->assertTrue($jenis->pernyataan);
        $this->assertSame('Keperluan', $jenis->skema_field_tambahan[1]['label']);

        $warga = User::factory()->create([
            'peran' => Peran::Warga,
            'status_akun' => StatusAkun::Aktif,
            'name' => 'Sulastri Uji',
        ]);
        $pengajuan = Pengajuan::query()->create([
            'kode_tiket' => 'LW-NYATA',
            'user_id' => $warga->id,
            'jenis_layanan_id' => $jenis->id,
            'data_form' => ['nama' => 'Sulastri Uji', 'keperluan' => 'Beasiswa'],
            'status' => StatusPengajuan::Diajukan,
            'status_pengantar_rt' => 'tidak_perlu',
            'tanggal_ajuan' => now(),
        ]);
        $operator = User::factory()->create(['peran' => Peran::Operator]);

        $this->actingAs($operator)->get(route('operator.pengajuan.lihat', $pengajuan))
            ->assertOk()
            ->assertSee('Pesan untuk pemohon')
            ->assertSee('Kirim ke pemohon')
            ->assertDontSee('Ajukan ke Lurah');

        $this->actingAs($operator)->post(route('operator.pengajuan.format.kirim', $pengajuan), [
            'pesan' => 'Silakan cetak dan tanda tangani.',
        ])->assertRedirect();

        $pengajuan->refresh();
        $this->assertSame(StatusPengajuan::Selesai, $pengajuan->status);
        $this->assertNotNull($pengajuan->file_hasil);
        $this->assertTrue(Notifikasi::query()->where('user_id', $warga->id)->where('isi', 'like', '%Silakan cetak dan tanda tangani.%')->exists());

        $unduh = $this->actingAs($warga)->get(route('berkas.format', $pengajuan))->assertOk()->getContent();
        $xml = $this->xml($unduh);
        $this->assertStringContainsString('Pernyataan Sulastri Uji untuk Beasiswa.', $xml);
        $this->assertStringNotContainsString('PEMERINTAH KABUPATEN', $xml);

        $this->actingAs($warga)->post(route('operator.pengajuan.format.kirim', $pengajuan), [
            'pesan' => 'Tidak boleh',
        ])->assertForbidden();
    }

    private function docx(string $isi): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>'.htmlspecialchars($isi, ENT_XML1).'</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();
        $byte = (string) file_get_contents($path);
        @unlink($path);

        return $byte;
    }

    private function xml(string $docx): string
    {
        $path = tempnam(sys_get_temp_dir(), 'out');
        file_put_contents($path, $docx);
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        return $xml;
    }
}
