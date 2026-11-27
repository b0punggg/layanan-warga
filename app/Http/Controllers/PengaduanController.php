<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use App\Services\AuditLogger;
use App\Services\PengirimPesan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class PengaduanController extends Controller
{
    public function buat(): View
    {
        $user = auth()->user();

        return view('pengaduan.buat', [
            'nama' => old('nama', $user?->name ?? ''),
            'noHp' => old('no_hp', $user?->no_hp ?? ''),
            'email' => old('email', $user?->email ?? ''),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $kunci = 'pengaduan:'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            return back()->withInput()->withErrors(['judul' => 'Terlalu banyak aduan. Coba lagi nanti.']);
        }

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'no_hp' => ['required', 'regex:/^08\d{8,12}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'judul' => ['required', 'string', 'max:150'],
            'isi' => ['required', 'string', 'max:2000'],
            'setuju' => ['accepted'],
        ], [
            'setuju.accepted' => 'Centang persetujuan sebelum mengirim.',
            'no_hp.regex' => 'Nomor HP diawali 08.',
        ]);

        RateLimiter::hit($kunci, 600);

        $pengaduan = Pengaduan::query()->create([
            'user_id' => $request->user()?->id,
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'],
            'email' => $data['email'] ?? null,
            'judul' => $data['judul'],
            'isi' => $data['isi'],
            'status' => StatusPengaduan::Baru,
        ]);

        AuditLogger::catat('buat_pengaduan', 'pengaduan:'.$pengaduan->id, null, $request->user()?->id);

        return redirect()->route('pengaduan.buat')->with('status', 'Aduan terkirim. Simpan nomor tiket ADU-'.$pengaduan->id.'.');
    }

    public function milik(): View
    {
        return view('pengaduan.milik', [
            'pengaduan' => Pengaduan::query()->where('user_id', auth()->id())->latest()->get(),
        ]);
    }

    public function index(): View
    {
        return view('pengaduan.index', [
            'pengaduan' => Pengaduan::query()->latest()->paginate(15),
        ]);
    }

    public function lihat(Pengaduan $pengaduan): View
    {
        return view('pengaduan.lihat', ['pengaduan' => $pengaduan]);
    }

    public function tanggapan(Request $request, Pengaduan $pengaduan, PengirimPesan $pesan): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:diproses,selesai'],
            'tanggapan' => ['required', 'string', 'max:2000'],
        ]);

        $pengaduan->update([
            'status' => $data['status'],
            'tanggapan' => $data['tanggapan'],
            'ditanggapi_oleh' => $request->user()->id,
            'ditanggapi_pada' => now(),
        ]);

        $pengaduan->load('pelapor');

        if ($pengaduan->pelapor !== null) {
            $pesan->keAkun($pengaduan->pelapor, 'Aduan "'.$pengaduan->judul.'" ditanggapi: '.$data['tanggapan']);
        }

        AuditLogger::catat('tanggapi_pengaduan', 'pengaduan:'.$pengaduan->id, ['status' => $data['status']]);

        return back()->with('status', 'Tanggapan tersimpan.');
    }
}
