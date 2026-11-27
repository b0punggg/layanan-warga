<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\JenisLayanan;
use App\Models\User;
use App\Services\NomorSurat;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(NomorSurat $nomorSurat): View
    {
        return view('admin.beranda', [
            'jumlahPeran' => User::query()->selectRaw('peran, count(*) as jumlah')->groupBy('peran')->pluck('jumlah', 'peran'),
            'jumlahLayanan' => JenisLayanan::query()->where('aktif', true)->count(),
            'audit' => AuditLog::query()->with('user')->latest('waktu')->limit(5)->get(),
            'contohNomor' => $nomorSurat->susun('SKU', 1, now()),
        ]);
    }
}
