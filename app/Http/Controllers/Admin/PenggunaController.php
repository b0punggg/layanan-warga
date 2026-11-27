<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanPenggunaRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(): View
    {
        return view('admin.pengguna.index', [
            'pengguna' => User::query()->orderBy('peran')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pengguna.buat', [
            'peranStaf' => [Peran::Operator, Peran::Lurah, Peran::Admin, Peran::RtRw],
        ]);
    }

    public function store(SimpanPenggunaRequest $request): RedirectResponse
    {
        $rt = $request->input('peran') === Peran::RtRw->value;
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'no_hp' => $request->input('no_hp') ?: null,
            'peran' => $request->string('peran')->toString(),
            'dukuh' => $rt ? $request->input('dukuh') : null,
            'rt' => $rt ? $request->input('rt') : null,
            'rw' => $rt ? $request->input('rw') : null,
            'status_akun' => StatusAkun::Aktif,
            'password' => $request->string('password')->toString(),
        ]);

        AuditLogger::catat('buat_pengguna', 'pengguna:'.$user->id, [
            'email' => $user->email,
            'peran' => $user->peran->value,
        ]);

        return redirect()->route('admin.pengguna.index')->with('status', 'Pengguna staf berhasil ditambahkan.');
    }

    public function ubahStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status_akun' => ['required', Rule::in([StatusAkun::Aktif->value, StatusAkun::Nonaktif->value])],
        ]);

        if ($user->is($request->user()) && $data['status_akun'] !== StatusAkun::Aktif->value) {
            return back()->withErrors([
                'status_akun' => 'Anda tidak dapat menonaktifkan akun yang sedang dipakai.',
            ]);
        }

        $lama = $user->status_akun->value;
        $user->update(['status_akun' => $data['status_akun']]);

        AuditLogger::catat('ubah_status_akun', 'pengguna:'.$user->id, [
            'dari' => $lama,
            'menjadi' => $data['status_akun'],
        ]);

        return back()->with('status', 'Status akun diperbarui.');
    }
}
