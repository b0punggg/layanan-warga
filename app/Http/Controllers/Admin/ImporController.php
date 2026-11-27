<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiwayatImpor;
use App\Services\BerkasPrivat;
use App\Services\ImporPenduduk;
use App\Services\PembacaTabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImporController extends Controller
{
    public function buat(): View
    {
        return view('admin.impor.buat', [
            'riwayat' => RiwayatImpor::query()->with('pelaku')->latest('waktu')->limit(10)->get(),
        ]);
    }

    public function pratinjau(Request $request, PembacaTabel $pembaca, ImporPenduduk $impor): View|RedirectResponse
    {
        $request->validate([
            'berkas' => ['required', 'file', 'max:5120', 'extensions:csv,txt,xlsx'],
        ]);

        try {
            $baris = $pembaca->baca($request->file('berkas'));
            $hasil = $impor->periksa($baris);
        } catch (RuntimeException $e) {
            return back()->withErrors(['berkas' => $e->getMessage()]);
        }

        $token = Str::random(40);
        Cache::put($this->kunci($request, $token), [
            'nama' => $request->file('berkas')->getClientOriginalName(),
            'hasil' => $hasil,
        ], now()->addMinutes(30));

        return view('admin.impor.pratinjau', [
            'token' => $token,
            'nama' => $request->file('berkas')->getClientOriginalName(),
            'pratinjau' => $hasil,
        ]);
    }

    public function simpan(Request $request, ImporPenduduk $impor): RedirectResponse
    {
        $token = $request->validate(['token' => ['required', 'string']])['token'];
        $simpan = Cache::pull($this->kunci($request, $token));
        abort_unless(is_array($simpan), 404);

        $riwayat = $impor->simpan($simpan['hasil']['baris'], $request->user(), $simpan['nama']);

        return redirect()->route('admin.impor.buat')->with('status', 'Impor selesai. '.$riwayat->baris_sukses.' baris tersimpan, '.$riwayat->baris_gagal.' gagal.');
    }

    public function gagal(Request $request, string $token, ImporPenduduk $impor): StreamedResponse
    {
        $simpan = Cache::get($this->kunci($request, $token));
        abort_unless(is_array($simpan), 404);
        $gagal = array_values(array_filter($simpan['hasil']['baris'], fn (array $baris) => $baris['mode'] === 'gagal'));
        $isi = $impor->csvGagal($gagal);

        return response()->streamDownload(function () use ($isi) {
            echo $isi;
        }, 'baris-gagal.csv', ['Content-Type' => 'text/csv']);
    }

    public function laporan(RiwayatImpor $riwayat, BerkasPrivat $berkas): StreamedResponse
    {
        abort_unless($riwayat->laporan_gagal_path, 404);
        $isi = $berkas->baca($riwayat->laporan_gagal_path);

        return response()->streamDownload(function () use ($isi) {
            echo $isi;
        }, 'laporan-gagal-'.$riwayat->id.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function kunci(Request $request, string $token): string
    {
        return 'impor.'.$request->user()->id.'.'.$token;
    }
}
