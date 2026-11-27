<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengajuan;
use App\Models\Surat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CekSuratController extends Controller
{
    public function form(Request $request): View
    {
        if ($request->filled('kode')) {
            return $this->lihat($request->string('kode')->toString());
        }

        return view('cek.form');
    }

    public function lihat(string $kode): View
    {
        $surat = Surat::query()
            ->with(['pengajuan.jenisLayanan', 'pengajuan.pemohon'])
            ->where('kode_verifikasi', strtolower(trim($kode)))
            ->first();

        $sah = $surat !== null
            && $surat->file_pdf !== null
            && $surat->pengajuan->status === StatusPengajuan::Selesai;

        return view('cek.hasil', [
            'sah' => $sah,
            'surat' => $sah ? $surat : null,
        ]);
    }
}
