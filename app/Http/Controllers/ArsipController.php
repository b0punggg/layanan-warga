<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArsipController extends Controller
{
    public function index(Request $request): View
    {
        $surat = Surat::query()
            ->with(['pengajuan.pemohon', 'pengajuan.jenisLayanan'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($q) {
                    $inner->where('nomor_surat', 'like', $q)
                        ->orWhereHas('pengajuan', fn ($pengajuan) => $pengajuan->whereHas('pemohon', fn ($pemohon) => $pemohon->where('name', 'like', $q)));
                });
            })
            ->latest('tanggal_terbit')
            ->paginate(20)
            ->withQueryString();

        return view('arsip.index', ['surat' => $surat]);
    }
}
