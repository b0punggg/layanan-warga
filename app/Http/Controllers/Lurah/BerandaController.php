<?php

namespace App\Http\Controllers\Lurah;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Models\PejabatKalurahan;
use App\Models\Pengajuan;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(): View
    {
        return view('lurah.beranda', [
            'menunggu' => Pengajuan::query()->where('status', StatusPengajuan::Diverifikasi)->count(),
            'selesai' => Pengajuan::query()->where('status', StatusPengajuan::Selesai)->count(),
            'pejabat' => PejabatKalurahan::query()->where('aktif', true)->get(),
        ]);
    }
}
