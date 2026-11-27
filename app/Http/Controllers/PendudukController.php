<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Enums\StatusDataPenduduk;
use App\Models\Penduduk;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PendudukController extends Controller
{
    public function index(Request $request): View
    {
        $penduduk = Penduduk::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q');
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', '%'.$q.'%')->orWhere('nik', 'like', '%'.$q.'%');
                });
            })
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return view('penduduk.index', ['penduduk' => $penduduk]);
    }

    public function ubah(Penduduk $penduduk): View
    {
        return view('penduduk.ubah', ['penduduk' => $penduduk]);
    }

    public function simpan(Request $request, Penduduk $penduduk): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'alamat_asli' => ['required', 'string'],
            'dukuh' => ['nullable', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'nik' => ['nullable', 'digits:16', Rule::unique('penduduk', 'nik')->ignore($penduduk->id)],
        ]);

        if ($request->user()->peran !== Peran::Admin) {
            unset($data['nik']);
        }

        $data['status_data'] = ($data['dukuh'] ?? null) && ($data['rt'] ?? null) && ($data['rw'] ?? null)
            ? StatusDataPenduduk::Terverifikasi
            : StatusDataPenduduk::Menunggu;

        $penduduk->update($data);
        AuditLogger::catat('ubah_penduduk', 'penduduk:'.$penduduk->id);

        return redirect()->route('penduduk.index')->with('status', 'Data penduduk diperbarui.');
    }
}
