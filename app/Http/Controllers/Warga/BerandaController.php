<?php

namespace App\Http\Controllers\Warga;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(): View
    {
        return view('warga.beranda', [
            'pengajuan' => auth()->user()->pengajuan()->latest()->limit(8)->get(),
        ]);
    }
}
