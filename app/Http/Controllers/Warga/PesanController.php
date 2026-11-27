<?php

namespace App\Http\Controllers\Warga;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\View\View;

class PesanController extends Controller
{
    public function index(): View
    {
        return view('warga.pesan', [
            'pesan' => Notifikasi::query()->where('user_id', auth()->id())->latest('waktu')->limit(30)->get(),
        ]);
    }
}
