<?php

namespace App\Http\Controllers\Operator;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Models\JenisLayanan;
use App\Models\Pengajuan;
use App\Services\PenerbitSurat;
use App\Services\PengisiTemplatWord;
use App\Services\PenulisSuratWord;
use App\Support\IsianSurat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PengajuanController extends Controller
{
    public function index(Request $request): View
    {
        $pengajuan = Pengajuan::query()
            ->with(['pemohon', 'jenisLayanan'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis_layanan_id', $request->integer('jenis')))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('tanggal_ajuan', '>=', $request->date('dari')))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('tanggal_ajuan', '<=', $request->date('sampai')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('operator.pengajuan.index', [
            'pengajuan' => $pengajuan,
            'jenis' => JenisLayanan::query()->orderBy('nama')->get(),
            'status' => StatusPengajuan::cases(),
        ]);
    }

    public function lihat(Pengajuan $pengajuan, PenerbitSurat $penerbit): View
    {
        $pengajuan->load(['pemohon.profilWarga.penduduk', 'jenisLayanan', 'berkas', 'riwayatStatus.pelaku', 'surat']);

        return view('operator.pengajuan.lihat', [
            'pengajuan' => $pengajuan,
            'usulanNomor' => $pengajuan->surat?->nomor_surat ?? $penerbit->usulan($pengajuan),
        ]);
    }

    public function sunting(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        abort_unless($pengajuan->status === StatusPengajuan::Diajukan, 403);
        $aturan = [];

        $pesan = [];

        foreach (app(IsianSurat::class)->baris($pengajuan->jenisLayanan) as $field) {
            $panjang = in_array($field['nama'], ['alamat', 'alamat_usaha', 'alamat_tujuan'], true) ? 500 : 255;
            $aturan['data.'.$field['nama']] = ['nullable', 'string', 'max:'.$panjang];
            $pilihan = array_column($field['pilihan'] ?? [], 'nilai');
            if ($pilihan !== []) {
                $aturan['data.'.$field['nama']][] = Rule::in($pilihan);
                $pesan['data.'.$field['nama'].'.in'] = $field['label'].' belum dipilih.';
            }
        }

        $data = $request->validate($aturan, $pesan);
        $pengajuan->update(['data_form' => array_merge($pengajuan->data_form ?? [], $data['data'] ?? [])]);

        return back()->with('status', 'Draf surat diperbarui.');
    }

    public function perbaikan(Request $request, Pengajuan $pengajuan, PenerbitSurat $penerbit): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['required', 'string', 'max:1000']]);

        return $this->jalankan(fn () => $penerbit->mintaPerbaikan($pengajuan, $request->user(), $data['catatan']));
    }

    public function tolak(Request $request, Pengajuan $pengajuan, PenerbitSurat $penerbit): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['required', 'string', 'max:1000']]);

        return $this->jalankan(fn () => $penerbit->tolak($pengajuan, $request->user(), $data['catatan']));
    }

    public function kirim(Request $request, Pengajuan $pengajuan, PenerbitSurat $penerbit): RedirectResponse
    {
        $data = $request->validate([
            'nomor_surat' => ['nullable', 'string', 'max:100'],
        ]);

        return $this->jalankan(fn () => $penerbit->kirimKeLurah($pengajuan, $request->user(), $data['nomor_surat'] ?? null));
    }

    public function naskah(Pengajuan $pengajuan, PenerbitSurat $penerbit): Response
    {
        $this->siapDiunduh($pengajuan);
        $nama = Str::slug($pengajuan->surat->nomor_surat ?: 'naskah').'.pdf';

        return response($penerbit->pdf($pengajuan), 200, $this->unduh($nama, 'application/pdf'));
    }

    public function naskahWord(Pengajuan $pengajuan, PenulisSuratWord $penulis): Response
    {
        $this->siapDiunduh($pengajuan);
        $nama = Str::slug($pengajuan->surat->nomor_surat ?: 'naskah').'.docx';

        return response($penulis->buat($pengajuan), 200, $this->unduh(
            $nama,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ));
    }

    public function terbit(Request $request, Pengajuan $pengajuan, PenerbitSurat $penerbit): RedirectResponse
    {
        $data = $request->validate([
            'berkas_tte' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'pesan' => ['nullable', 'string', 'max:1000'],
        ], [
            'berkas_tte.required' => 'Unggah PDF yang sudah ditandatangani di SIMARDA.',
            'berkas_tte.mimes' => 'Berkas harus berupa PDF.',
            'berkas_tte.max' => 'PDF terlalu besar. Batasnya 10 MB.',
            'pesan.max' => 'Pesan untuk pemohon terlalu panjang.',
        ]);

        $isi = (string) file_get_contents($data['berkas_tte']->getRealPath());

        return $this->jalankan(fn () => $penerbit->terbitkan($pengajuan, $request->user(), $isi, $data['pesan'] ?? null));
    }

    public function format(Pengajuan $pengajuan, PenerbitSurat $penerbit, PengisiTemplatWord $pengisi): Response
    {
        $this->siapFormat($pengajuan);
        $nama = Str::slug($pengajuan->kode_tiket.'-format').'.docx';

        return response($this->berkasFormat($pengajuan, $penerbit, $pengisi), 200, $this->unduh(
            $nama,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ));
    }

    public function kirimFormat(Request $request, Pengajuan $pengajuan, PenerbitSurat $penerbit, PengisiTemplatWord $pengisi): RedirectResponse
    {
        $this->siapFormat($pengajuan);
        $data = $request->validate([
            'pesan' => ['required', 'string', 'max:1000'],
        ], [
            'pesan.required' => 'Pesan untuk pemohon wajib diisi.',
            'pesan.max' => 'Pesan untuk pemohon terlalu panjang.',
        ]);

        try {
            $berkas = $this->berkasFormat($pengajuan, $penerbit, $pengisi);
        } catch (RuntimeException $e) {
            return back()->withErrors(['aksi' => $e->getMessage()])->withInput();
        }

        return $this->jalankan(function () use ($penerbit, $pengajuan, $request, $berkas, $data) {
            $penerbit->kirimFormat($pengajuan, $request->user(), $berkas, $data['pesan']);
        });
    }

    private function siapFormat(Pengajuan $pengajuan): void
    {
        $pengajuan->loadMissing('jenisLayanan');
        abort_unless(
            $pengajuan->jenisLayanan->pernyataan && $pengajuan->status === StatusPengajuan::Diajukan && filled($pengajuan->jenisLayanan->berkas_template),
            404,
        );
    }

    private function berkasFormat(Pengajuan $pengajuan, PenerbitSurat $penerbit, PengisiTemplatWord $pengisi): string
    {
        $pengajuan->loadMissing(['jenisLayanan', 'pemohon.profilWarga.penduduk']);

        return $pengisi->isi((string) $pengajuan->jenisLayanan->berkas_template, $penerbit->dataWarga($pengajuan));
    }

    private function jalankan(callable $kerja): RedirectResponse
    {
        try {
            $kerja();
        } catch (RuntimeException $e) {
            return back()->withErrors(['aksi' => $e->getMessage()]);
        }

        return back()->with('status', 'Status pengajuan diperbarui.');
    }

    private function siapDiunduh(Pengajuan $pengajuan): void
    {
        abort_unless(
            $pengajuan->status === StatusPengajuan::Disetujui && $pengajuan->surat !== null,
            404,
        );
    }

    /**
     * @return array<string, string>
     */
    private function unduh(string $nama, string $tipe): array
    {
        return [
            'Content-Type' => $tipe,
            'Content-Disposition' => 'attachment; filename="'.$nama.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];
    }
}
