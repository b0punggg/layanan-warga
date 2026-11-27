<?php

namespace App\Http\Controllers\Operator;

use App\Enums\StatusPengajuan;
use App\Enums\StatusVerifikasiProfil;
use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Models\ProfilWarga;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(): View
    {
        return view('operator.beranda', [
            'menungguAkun' => ProfilWarga::query()
                ->where('status_verifikasi', StatusVerifikasiProfil::Menunggu)
                ->count(),
            'diajukan' => Pengajuan::query()->where('status', StatusPengajuan::Diajukan)->count(),
            'perluPerbaikan' => Pengajuan::query()->where('status', StatusPengajuan::PerluPerbaikan)->count(),
            'menungguLurah' => Pengajuan::query()->where('status', StatusPengajuan::Diverifikasi)->count(),
            'menungguUnggah' => Pengajuan::query()->where('status', StatusPengajuan::Disetujui)->count(),
        ]);
    }
}
