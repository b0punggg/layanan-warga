<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasukRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MasukController extends Controller
{
    public function create(): View
    {
        return view('auth.masuk', [
            'akunPercobaan' => app()->environment('local') ? $this->akunPercobaan() : [],
            'kataSandiPercobaan' => app()->environment('local')
                ? (string) config('surat.kata_sandi_percobaan')
                : null,
        ]);
    }

    public function store(MasukRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route($request->user()->berandaRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('awal');
    }

    /**
     * @return list<array{peran: string, email: string, nik: string}>
     */
    private function akunPercobaan(): array
    {
        return [
            ['peran' => 'Admin sistem', 'email' => 'admin@kalurahan.local', 'nik' => '—'],
            ['peran' => 'Operator', 'email' => 'operator@kalurahan.local', 'nik' => '—'],
            ['peran' => 'Lurah', 'email' => 'lurah@kalurahan.local', 'nik' => '—'],
            ['peran' => 'Warga', 'email' => 'warga@kalurahan.local', 'nik' => '3400000000000001'],
            ['peran' => 'RT/RW', 'email' => 'rt@kalurahan.local', 'nik' => '—'],
        ];
    }
}
