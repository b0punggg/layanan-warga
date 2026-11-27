<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\JenisLayanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class TemplatOperatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_melihat_dan_mengunduh_contoh_template(): void
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
        $operator = User::factory()->create(['peran' => Peran::Operator]);

        $this->actingAs($operator)->get(route('operator.templat.index'))
            ->assertOk()
            ->assertSee('Template surat')
            ->assertSee('Surat Keterangan Usaha');

        $this->actingAs($operator)->get(route('operator.templat.lihat', $jenis))
            ->assertOk()
            ->assertSee('SURAT KETERANGAN USAHA')
            ->assertSee('400.10.5/001')
            ->assertSee('Isian surat ini terkunci')
            ->assertSee('Drs. Anang Zamroni, M.S.I.');

        $respon = $this->actingAs($operator)->get(route('operator.templat.word', $jenis));
        $respon->assertOk();
        $this->assertStringStartsWith('PK', $respon->getContent());
        $this->assertStringContainsString('[onshow.ttd_19254;ope=changepic;noerr]', $this->xml($respon->getContent()));

        $this->actingAs(User::factory()->create(['peran' => Peran::Warga]))
            ->get(route('operator.templat.index'))
            ->assertForbidden();
    }

    public function test_isian_dinamis_bisa_diubah_dan_surat_resmi_terkunci(): void
    {
        $nikah = JenisLayanan::query()->where('kode', 'NIKAH')->firstOrFail();
        $operator = User::factory()->create(['peran' => Peran::Operator]);

        $this->actingAs($operator)->put(route('operator.templat.simpan', $nikah), [
            'naskah' => "Pengantar nikah untuk {{nama}}.\nKeperluan: {{keperluan_khusus}}.",
            'field' => [
                ['nama' => 'keperluan_khusus', 'label' => 'Keperluan khusus', 'wajib' => '1'],
            ],
        ])->assertRedirect(route('operator.templat.lihat', $nikah));

        $nikah->refresh();
        $this->assertSame('Keperluan khusus', $nikah->skema_field_tambahan[0]['label']);
        $this->assertStringContainsString('{{keperluan_khusus}}', $nikah->naskah);

        $sku = JenisLayanan::query()->create([
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

        $this->actingAs($operator)->put(route('operator.templat.simpan', $sku), [
            'naskah' => 'Diubah',
        ])->assertForbidden();
        $this->assertNull($sku->fresh()->naskah);

        $xml = $this->xml($this->actingAs($operator)->get(route('operator.templat.word', $nikah))->assertOk()->getContent());
        $this->assertStringContainsString('[onshow.ttd_19254;ope=changepic;noerr]', $xml);
        $this->assertStringContainsString('Drs. Anang Zamroni, M.S.I.', $xml);
        $this->assertStringContainsString('Pengantar nikah untuk Sulastri', $xml);
    }

    public function test_sktm_mengikuti_naskah_resmi_dan_dua_status_kartu(): void
    {
        $jenis = JenisLayanan::query()->create([
            'nama' => 'Surat Keterangan Tidak Mampu',
            'kode' => 'SKTM',
            'butuh_pengantar_rt' => false,
            'mode_pengantar' => 'unggah',
            'daftar_syarat' => [],
            'skema_field_tambahan' => [],
            'format_nomor' => '400.3/{nomor}',
            'template_surat' => 'surat.templates.sktm',
            'aktif' => true,
        ]);
        $operator = User::factory()->create(['peran' => Peran::Operator]);

        $this->actingAs($operator)->get(route('operator.templat.lihat', $jenis))
            ->assertOk()
            ->assertSee('SURAT KETERANGAN TIDAK MAMPU')
            ->assertSee('400.3/001')
            ->assertSee('yang terdaftar dalam Kartu Keluarga Miskin atau Kartu Keluarga Rentan Miskin')
            ->assertSee('Budi Santoso')
            ->assertSee('Status dalam Kartu Keluarga Miskin atau Kartu Keluarga Rentan Miskin')
            ->assertSee('Tempat, tanggal lahir anak');

        $xml = $this->xml($this->actingAs($operator)->get(route('operator.templat.word', $jenis))->assertOk()->getContent());
        $this->assertStringContainsString('yang terdaftar dalam Kartu Keluarga Miskin atau Kartu Keluarga Rentan Miskin', $xml);
        $this->assertStringContainsString('Budi Santoso', $xml);
        $this->assertStringContainsString('Tempat, Tgl Lahir', $xml);
        $this->assertStringContainsString('[onshow.ttd_19254;ope=changepic;noerr]', $xml);
        $this->assertStringContainsString('400.3/001', $xml);
    }

    public function test_sktm_kesehatan_memakai_naskah_dan_nomor_sendiri(): void
    {
        $jenis = JenisLayanan::query()->where('kode', 'SKKES')->firstOrFail();
        $operator = User::factory()->create(['peran' => Peran::Operator]);

        $this->actingAs($operator)->get(route('operator.templat.lihat', $jenis))
            ->assertOk()
            ->assertSee('400.7/001')
            ->assertSee('yang termasuk dalam Kartu Keluarga Miskin dan Kartu Keluarga Rentan Miskin')
            ->assertSee('Berobat')
            ->assertSee('Tujuan')
            ->assertSee('Status dalam Kartu Keluarga Miskin dan Kartu Keluarga Rentan Miskin')
            ->assertDontSee('Nama anak');

        $xml = $this->xml($this->actingAs($operator)->get(route('operator.templat.word', $jenis))->assertOk()->getContent());
        $this->assertStringContainsString('400.7/001', $xml);
        $this->assertStringContainsString('yang termasuk dalam Kartu Keluarga Miskin dan Kartu Keluarga Rentan Miskin', $xml);
        $this->assertStringContainsString('Berobat', $xml);
        $this->assertStringContainsString('[onshow.ttd_19254;ope=changepic;noerr]', $xml);
    }

    private function xml(string $docx): string
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
