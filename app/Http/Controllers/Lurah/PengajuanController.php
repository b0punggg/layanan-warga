<?php

namespace App\Http\Controllers\Lurah;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Services\PenerbitSurat;
use App\Services\PenyusunNaskah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PengajuanController extends Controller
{
    public function index(): View
    {
        return view('lurah.pengajuan.index', [
            'pengajuan' => Pengajuan::query()
                ->with(['pemohon', 'jenisLayanan', 'surat'])
                ->where('status', StatusPengajuan::Diverifikasi)
                ->oldest('tanggal_ajuan')
                ->get(),
        ]);
    }

    public function lihat(Pengajuan $pengajuan, PenerbitSurat $penerbit, PenyusunNaskah $naskah): View
    {
        abort_unless($pengajuan->status === StatusPengajuan::Diverifikasi, 404);
        $pengajuan->load(['pemohon.profilWarga', 'jenisLayanan', 'berkas', 'surat']);
        $contoh = $penerbit->dataWarga($pengajuan);
        $nomor = $pengajuan->surat->nomor_surat ?? '………………';

        return view('lurah.pengajuan.lihat', [
            'pengajuan' => $pengajuan,
            'contoh' => $contoh,
            'badan' => $naskah->html($pengajuan->jenisLayanan->naskah, array_merge($contoh, ['nomor' => $nomor])),
        ]);
    }

    public function setujui(Request $request, Pengajuan $pengajuan, PenerbitSurat $penerbit): RedirectResponse
    {
        return $this->jalankan(fn () => $penerbit->setujui($pengajuan, $request->user()));
    }

    public function tolak(Request $request, Pengajuan $pengajuan, PenerbitSurat $penerbit): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['required', 'string', 'max:1000']]);

        return $this->jalankan(fn () => $penerbit->tolak($pengajuan, $request->user(), $data['catatan']));
    }

    public function massal(Request $request, PenerbitSurat $penerbit): RedirectResponse
    {
        $data = $request->validate([
            'pengajuan' => ['required', 'array', 'min:1'],
            'pengajuan.*' => ['integer'],
        ]);

        $jumlah = 0;

        foreach (Pengajuan::query()->whereIn('id', $data['pengajuan'])->where('status', StatusPengajuan::Diverifikasi)->get() as $pengajuan) {
            $penerbit->setujui($pengajuan, $request->user());
            $jumlah++;
        }

        return back()->with('status', $jumlah.' surat disetujui. Operator mengunggah PDF dari SIMARDA.');
    }

    private function jalankan(callable $kerja): RedirectResponse
    {
        try {
            $kerja();
        } catch (RuntimeException $e) {
            return back()->withErrors(['aksi' => $e->getMessage()]);
        }

        return redirect()->route('lurah.pengajuan.index')->with('status', 'Keputusan tersimpan.');
    }
}
