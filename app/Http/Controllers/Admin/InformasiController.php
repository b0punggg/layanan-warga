<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Informasi;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InformasiController extends Controller
{
    public function index(): View
    {
        return view('admin.informasi.index', [
            'informasi' => Informasi::query()->latest()->get(),
        ]);
    }

    public function buat(): View
    {
        return view('admin.informasi.formulir', ['informasi' => new Informasi(['terbit' => false])]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $informasi = Informasi::query()->create($this->data($request));
        AuditLogger::catat('buat_informasi', 'informasi:'.$informasi->id, ['judul' => $informasi->judul]);

        return redirect()->route('admin.informasi.index')->with('status', 'Informasi tersimpan.');
    }

    public function ubah(Informasi $informasi): View
    {
        return view('admin.informasi.formulir', ['informasi' => $informasi]);
    }

    public function perbarui(Request $request, Informasi $informasi): RedirectResponse
    {
        $informasi->update($this->data($request, $informasi));
        AuditLogger::catat('ubah_informasi', 'informasi:'.$informasi->id, ['judul' => $informasi->judul]);

        return redirect()->route('admin.informasi.index')->with('status', 'Informasi diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function data(Request $request, ?Informasi $lama = null): array
    {
        $valid = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'ringkasan' => ['required', 'string', 'max:200'],
            'isi' => ['required', 'string', 'max:10000'],
            'terbit' => ['nullable', 'boolean'],
        ]);

        $terbit = $request->boolean('terbit');

        return [
            'user_id' => $request->user()->id,
            'judul' => $valid['judul'],
            'ringkasan' => $valid['ringkasan'],
            'isi' => $valid['isi'],
            'terbit' => $terbit,
            'diterbitkan_pada' => $terbit ? ($lama?->diterbitkan_pada ?? now()) : null,
        ];
    }
}
