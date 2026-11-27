<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModePengantar;
use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanLayananRequest;
use App\Models\JenisLayanan;
use App\Services\AuditLogger;
use App\Services\BerkasPrivat;
use App\Services\PengisiTemplatWord;
use App\Support\IsianSurat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class LayananController extends Controller
{
    public function index(): View
    {
        return view('admin.layanan.index', [
            'layanan' => JenisLayanan::query()->orderBy('nama')->get(),
        ]);
    }

    public function buat(): View
    {
        return view('admin.layanan.formulir', [
            'jenis' => new JenisLayanan([
                'aktif' => true,
                'estimasi_hari' => 3,
                'mode_pengantar' => ModePengantar::Unggah,
                'daftar_syarat' => [],
                'skema_field_tambahan' => [],
            ]),
        ]);
    }

    public function simpan(SimpanLayananRequest $request, BerkasPrivat $berkas, PengisiTemplatWord $pengisi): RedirectResponse
    {
        $template = $this->template($request, $pengisi);
        $jenis = JenisLayanan::query()->create($this->atribut($request, null));
        $this->simpanTemplate($jenis, $template, $berkas);
        AuditLogger::catat('buat_layanan', 'jenis_layanan:'.$jenis->id, ['kode' => $jenis->kode]);

        return redirect()->route('admin.layanan.index')->with('status', 'Jenis layanan ditambahkan.');
    }

    public function ubah(JenisLayanan $jenisLayanan): View
    {
        return view('admin.layanan.formulir', ['jenis' => $jenisLayanan]);
    }

    public function perbarui(SimpanLayananRequest $request, JenisLayanan $jenisLayanan, BerkasPrivat $berkas, PengisiTemplatWord $pengisi): RedirectResponse
    {
        $template = $this->template($request, $pengisi);
        $jenisLayanan->update($this->atribut($request, $jenisLayanan));
        $this->simpanTemplate($jenisLayanan, $template, $berkas);
        AuditLogger::catat('ubah_layanan', 'jenis_layanan:'.$jenisLayanan->id, ['kode' => $jenisLayanan->kode]);

        return redirect()->route('admin.layanan.index')->with('status', 'Jenis layanan diperbarui.');
    }

    public function status(JenisLayanan $jenisLayanan): RedirectResponse
    {
        $jenisLayanan->update(['aktif' => ! $jenisLayanan->aktif]);
        AuditLogger::catat('ubah_status_layanan', 'jenis_layanan:'.$jenisLayanan->id, [
            'kode' => $jenisLayanan->kode,
            'aktif' => $jenisLayanan->aktif,
        ]);

        $pesan = $jenisLayanan->aktif
            ? $jenisLayanan->nama.' diaktifkan. Warga dapat mengajukan surat ini.'
            : $jenisLayanan->nama.' dinonaktifkan. Warga tidak dapat mengajukan surat baru.';

        return redirect()->route('admin.layanan.index')->with('status', $pesan);
    }

    /**
     * @return array<string, mixed>
     */
    private function atribut(SimpanLayananRequest $request, ?JenisLayanan $jenis): array
    {
        $syarat = $this->daftar($request->input('syarat'));
        $field = $this->daftar($request->input('field'));
        $terlarang = ['nama', 'nik', 'alamat', 'nomor', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'pekerjaan'];
        $bentrok = array_intersect(array_column($field, 'nama'), $terlarang);

        if ($bentrok !== []) {
            throw ValidationException::withMessages([
                'field' => 'Nama isian tidak boleh memakai '.implode(', ', $bentrok).'.',
            ]);
        }

        $kodeTerkunci = $jenis !== null && $jenis->pengajuan()->exists();
        $naskah = trim((string) $request->input('naskah'));

        return [
            'nama' => $request->string('nama')->toString(),
            'kode' => $kodeTerkunci ? $jenis->kode : $request->string('kode')->toString(),
            'deskripsi' => $request->input('deskripsi') ?: null,
            'estimasi_hari' => $request->integer('estimasi_hari'),
            'aktif' => $request->boolean('aktif'),
            'pernyataan' => $request->boolean('pernyataan'),
            'butuh_pengantar_rt' => $request->boolean('butuh_pengantar_rt'),
            'mode_pengantar' => $request->input('mode_pengantar') ?: ModePengantar::Unggah->value,
            'daftar_syarat' => $syarat,
            'skema_field_tambahan' => $request->boolean('pernyataan') && $jenis !== null ? $jenis->skema_field_tambahan : $field,
            'template_surat' => $jenis->template_surat ?? 'surat.templates.dinamis',
            'naskah' => $request->boolean('pernyataan') || $naskah === '' ? null : $naskah,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $baris
     * @return list<array{nama: string, label: string, wajib: bool, tipe?: string}>
     */
    private function daftar(?array $baris): array
    {
        $hasil = [];

        foreach ($baris ?? [] as $item) {
            $nama = strtolower(trim((string) ($item['nama'] ?? '')));
            $label = trim((string) ($item['label'] ?? ''));

            if ($nama === '' || $label === '' || isset($hasil[$nama])) {
                continue;
            }

            $hasil[$nama] = [
                'nama' => $nama,
                'label' => $label,
                'wajib' => (bool) ($item['wajib'] ?? false),
                'tipe' => 'text',
            ];
        }

        return array_values($hasil);
    }

    private function template(SimpanLayananRequest $request, PengisiTemplatWord $pengisi): ?string
    {
        if (! $request->boolean('pernyataan')) {
            return null;
        }

        $file = $request->file('template_word');

        if ($file === null) {
            return null;
        }

        $isi = (string) file_get_contents($file->getRealPath());

        try {
            $penanda = $pengisi->penanda($isi);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['template_word' => $e->getMessage()]);
        }

        if ($penanda === []) {
            throw ValidationException::withMessages([
                'template_word' => 'Template harus memuat penanda seperti {{nama}}.',
            ]);
        }

        return $isi;
    }

    private function simpanTemplate(JenisLayanan $jenis, ?string $isi, BerkasPrivat $berkas): void
    {
        if ($isi === null) {
            return;
        }

        if ($jenis->berkas_template) {
            $berkas->hapus($jenis->berkas_template);
        }

        $penanda = app(PengisiTemplatWord::class)->penanda($isi);
        $jenis->update([
            'berkas_template' => $berkas->simpan($isi, 'template/'.$jenis->id.'.docx'),
            'skema_field_tambahan' => app(IsianSurat::class)->dariPenanda($penanda),
            'naskah' => null,
        ]);
    }
}
