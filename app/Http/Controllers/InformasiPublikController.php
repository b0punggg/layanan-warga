<?php

namespace App\Http\Controllers;

use App\Models\Informasi;
use Illuminate\View\View;

class InformasiPublikController extends Controller
{
    public function index(): View
    {
        return view('informasi.index', [
            'informasi' => Informasi::query()->where('terbit', true)->latest('diterbitkan_pada')->get(),
        ]);
    }

    public function lihat(Informasi $informasi): View
    {
        abort_unless($informasi->terbit, 404);

        return view('informasi.lihat', ['informasi' => $informasi]);
    }
}
