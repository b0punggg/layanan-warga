<?php

namespace App\Http\Middleware;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePeran
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        $menungguBoleh = $user !== null
            && $user->status_akun === StatusAkun::Menunggu
            && $user->peran === Peran::Warga
            && $request->routeIs('warga.menunggu', 'warga.profil', 'warga.profil.simpan');

        if ($user === null || ($user->status_akun !== StatusAkun::Aktif && ! $menungguBoleh)) {
            abort(403, 'Akun ini tidak dapat dipakai.');
        }

        if (! in_array($user->peran->value, $peran, true)) {
            abort(403, 'Halaman ini di luar peran Anda.');
        }

        return $next($request);
    }
}
