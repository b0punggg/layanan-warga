<?php

namespace App\Http\Controllers\Lurah;

use App\Http\Controllers\Controller;
use App\Models\PejabatKalurahan;
use App\Services\BerkasPrivat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TandaTanganController extends Controller
{
    public function ubah(): View
    {
        return view('lurah.tanda-tangan', [
            'pejabat' => PejabatKalurahan::query()->where('user_id', auth()->id())->where('aktif', true)->first(),
        ]);
    }

    public function simpan(Request $request, BerkasPrivat $berkas): RedirectResponse
    {
        $data = $request->validate([
            'file_ttd' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:1024'],
        ]);

        $pejabat = PejabatKalurahan::query()->where('user_id', $request->user()->id)->where('aktif', true)->first();

        if ($pejabat === null) {
            $pejabat = PejabatKalurahan::query()->create([
                'user_id' => $request->user()->id,
                'nama' => $request->user()->name,
                'jabatan' => 'Lurah',
                'aktif' => true,
            ]);
        }

        if ($pejabat->file_ttd) {
            $berkas->hapus($pejabat->file_ttd);
        }

        $file = $data['file_ttd'];
        $pejabat->update([
            'file_ttd' => $berkas->simpanUnggahan($file, 'ttd/'.$pejabat->id.'.'.$file->extension()),
        ]);

        return back()->with('status', 'Gambar tanda tangan tersimpan.');
    }
}
