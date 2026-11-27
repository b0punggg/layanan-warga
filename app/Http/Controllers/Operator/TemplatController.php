<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\JenisLayanan;
use App\Services\AuditLogger;
use App\Services\NomorSurat;
use App\Services\PengisiTemplatWord;
use App\Services\PenulisSuratWord;
use App\Services\PenyusunNaskah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TemplatController extends Controller
{
    public function index(): View
    {
        return view('operator.templat.index', [
            'layanan' => JenisLayanan::query()->orderBy('nama')->get(),
        ]);
    }

    public function lihat(JenisLayanan $jenisLayanan, NomorSurat $nomorSurat, PenyusunNaskah $naskah, PenulisSuratWord $penulis): View
    {
        abort_unless(view()->exists($jenisLayanan->tampilanSurat()), 404);
        $nomor = $nomorSurat->susun($jenisLayanan->kode, 1, now(), $jenisLayanan->format_nomor);
        $contoh = $penulis->dataContoh();

        return view('operator.templat.lihat', [
            'jenis' => $jenisLayanan,
            'nomor' => $nomor,
            'contoh' => $contoh,
            'badan' => $naskah->html($jenisLayanan->naskah, array_merge($contoh, ['nomor' => $nomor])),
        ]);
    }

    public function word(JenisLayanan $jenisLayanan, NomorSurat $nomorSurat, PenulisSuratWord $penulis, PengisiTemplatWord $pengisi): Response
    {
        if ($jenisLayanan->pernyataan) {
            abort_unless(filled($jenisLayanan->berkas_template), 404);
            $nama = Str::slug($jenisLayanan->kode.'-contoh').'.docx';

            return response($pengisi->isi($jenisLayanan->berkas_template, $penulis->dataContoh()), 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="'.$nama.'"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        abort_unless(view()->exists($jenisLayanan->tampilanSurat()), 404);
        $nomor = $nomorSurat->susun($jenisLayanan->kode, 1, now(), $jenisLayanan->format_nomor);
        $nama = Str::slug($jenisLayanan->kode.'-contoh').'.docx';

        return response($penulis->contoh($jenisLayanan, $nomor), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$nama.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function simpan(Request $request, JenisLayanan $jenisLayanan): RedirectResponse
    {
        abort_if($jenisLayanan->isianTerkunci(), 403);

        $data = $request->validate([
            'naskah' => ['required', 'string', 'max:8000'],
            'field' => ['nullable', 'array', 'max:12'],
            'field.*.nama' => ['nullable', 'regex:/^[A-Za-z0-9_]{0,30}$/'],
            'field.*.label' => ['nullable', 'string', 'max:80'],
        ], [
            'naskah.required' => 'Naskah surat wajib diisi.',
            'naskah.max' => 'Naskah surat terlalu panjang.',
            'field.max' => 'Isian tambahan paling banyak 12.',
            'field.*.nama.regex' => 'Nama isian hanya huruf, angka, dan garis bawah.',
            'field.*.label.max' => 'Label isian terlalu panjang.',
        ]);

        $field = $this->field($data['field'] ?? []);
        $terlarang = ['nama', 'nik', 'alamat', 'nomor', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'pekerjaan', 'warga_negara', 'agama'];
        $bentrok = array_intersect(array_column($field, 'nama'), $terlarang);

        if ($bentrok !== []) {
            throw ValidationException::withMessages([
                'field' => 'Nama isian tidak boleh memakai '.implode(', ', $bentrok).'.',
            ]);
        }

        $jenisLayanan->update([
            'naskah' => trim($data['naskah']),
            'skema_field_tambahan' => $field,
        ]);
        AuditLogger::catat('ubah_isian_surat', 'jenis_layanan:'.$jenisLayanan->id, ['kode' => $jenisLayanan->kode]);

        return redirect()->route('operator.templat.lihat', $jenisLayanan)->with('status', 'Isian template disimpan.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $baris
     * @return list<array{nama: string, label: string, wajib: bool, tipe: string}>
     */
    private function field(array $baris): array
    {
        $hasil = [];

        foreach ($baris as $item) {
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
}
