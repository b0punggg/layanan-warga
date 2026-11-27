<?php

namespace App\Http\Controllers\Rt;

use App\Enums\StatusPengajuan;
use App\Enums\StatusPengantarRt;
use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Services\PenerbitSurat;
use App\Services\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PengajuanController extends Controller
{
    public function index(Request $request): View
    {
        return view('rt.pengajuan.index', [
            'pengajuan' => $this->antrean($request)->latest('tanggal_ajuan')->get(),
            'wilayah' => $this->wilayah($request),
        ]);
    }

    public function lihat(Request $request, Pengajuan $pengajuan): View
    {
        $this->milikWilayah($request, $pengajuan);
        $pengajuan->load(['pemohon.profilWarga', 'jenisLayanan']);

        return view('rt.pengajuan.lihat', ['pengajuan' => $pengajuan]);
    }

    public function setujui(Request $request, Pengajuan $pengajuan, PenerbitSurat $penerbit): RedirectResponse
    {
        $this->milikWilayah($request, $pengajuan);

        return $this->jalankan(function () use ($pengajuan, $penerbit, $request): void {
            $penerbit->setujuiPengantar($pengajuan, $request->user());
        });
    }

    public function tolak(Request $request, Pengajuan $pengajuan, PenerbitSurat $penerbit): RedirectResponse
    {
        $this->milikWilayah($request, $pengajuan);
        $data = $request->validate(['catatan' => ['required', 'string', 'max:1000']]);

        return $this->jalankan(function () use ($pengajuan, $penerbit, $request, $data): void {
            $penerbit->tolakPengantar($pengajuan, $request->user(), $data['catatan']);
        });
    }

    private function antrean(Request $request)
    {
        $wilayah = $this->wilayah($request);

        return Pengajuan::query()
            ->with(['pemohon.profilWarga', 'jenisLayanan'])
            ->where('status', StatusPengajuan::Diajukan)
            ->where('status_pengantar_rt', StatusPengantarRt::MenungguRt)
            ->whereHas('pemohon.profilWarga', function ($query) use ($wilayah): void {
                $query->whereIn('rt', $wilayah['padan_rt'])->whereIn('rw', $wilayah['padan_rw']);

                if ($wilayah['dukuh'] !== null) {
                    $query->whereRaw('lower(dukuh) = ?', [$wilayah['dukuh']]);
                }
            });
    }

    private function milikWilayah(Request $request, Pengajuan $pengajuan): void
    {
        abort_unless($this->antrean($request)->whereKey($pengajuan->id)->exists()
            || $this->sudahDitangani($request, $pengajuan), 403);
    }

    private function sudahDitangani(Request $request, Pengajuan $pengajuan): bool
    {
        if (! in_array($pengajuan->status_pengantar_rt, [StatusPengantarRt::DisetujuiRt, StatusPengantarRt::DitolakRt], true)) {
            return false;
        }

        $profil = $pengajuan->pemohon->profilWarga;
        $wilayah = $this->wilayah($request);

        if ($profil === null || ! in_array($profil->rt, $wilayah['padan_rt'], true) || ! in_array($profil->rw, $wilayah['padan_rw'], true)) {
            return false;
        }

        if ($wilayah['dukuh'] !== null && mb_strtolower((string) $profil->dukuh) !== $wilayah['dukuh']) {
            return false;
        }

        return true;
    }

    /**
     * @return array{dukuh: ?string, padan_rt: list<string>, padan_rw: list<string>}
     */
    private function wilayah(Request $request): array
    {
        $user = $request->user();
        $rt = Wilayah::kode($user->rt);
        $rw = Wilayah::kode($user->rw);

        return [
            'dukuh' => filled($user->dukuh) ? mb_strtolower($user->dukuh) : null,
            'padan_rt' => array_values(array_unique(array_filter([$rt, ltrim((string) $rt, '0')]))),
            'padan_rw' => array_values(array_unique(array_filter([$rw, ltrim((string) $rw, '0')]))),
        ];
    }

    private function jalankan(callable $kerja): RedirectResponse
    {
        try {
            $kerja();
        } catch (RuntimeException $e) {
            return back()->withErrors(['aksi' => $e->getMessage()]);
        }

        return redirect()->route('rt.beranda')->with('status', 'Keputusan pengantar tersimpan.');
    }
}
