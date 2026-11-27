<?php

namespace App\Http\Controllers;

use App\Enums\StatusAkun;
use Illuminate\Http\RedirectResponse;

class BerandaController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->status_akun === StatusAkun::Menunggu) {
            return redirect()->route('warga.menunggu');
        }

        return redirect()->route($user->berandaRoute());
    }
}
