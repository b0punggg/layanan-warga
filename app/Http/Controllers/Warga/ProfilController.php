<?php

namespace App\Http\Controllers\Warga;

use App\Enums\StatusVerifikasiProfil;
use App\Http\Controllers\Controller;
use App\Services\BerkasPrivat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function lihat(): View
    {
        $profil = auth()->user()->profilWarga;

        return view('warga.profil', [
            'profil' => $profil,
            'menunggu' => auth()->user()->status_akun->value === 'menunggu',
        ]);
    }

    public function simpan(Request $request, BerkasPrivat $berkas): RedirectResponse
    {
        $profil = auth()->user()->profilWarga;
        abort_unless($profil, 404);

        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'no_hp' => ['required', 'regex:/^08\d{8,12}$/'],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'dukuh' => ['required', 'string', 'max:100'],
            'rt' => ['required', 'string', 'max:5'],
            'rw' => ['required', 'string', 'max:5'],
            'alamat_lengkap' => ['required', 'string', 'max:500'],
            'foto_ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'foto_selfie' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Nomor HP harus diawali 08 dan berisi 10 sampai 14 angka.',
            'email.email' => 'Email harus berupa alamat email yang benar.',
            'dukuh.required' => 'Dukuh wajib diisi.',
            'rt.required' => 'RT wajib diisi.',
            'rw.required' => 'RW wajib diisi.',
            'alamat_lengkap.required' => 'Alamat wajib diisi.',
            'foto_ktp.mimes' => 'Foto KTP harus JPG atau PNG.',
            'foto_ktp.max' => 'Foto KTP paling besar 2 MB.',
            'foto_selfie.mimes' => 'Swafoto harus JPG atau PNG.',
            'foto_selfie.max' => 'Swafoto paling besar 2 MB.',
        ]);

        $profil->fill([
            'email' => $data['email'] ?: null,
            'no_hp' => $data['no_hp'],
            'pekerjaan' => $data['pekerjaan'],
            'dukuh' => $data['dukuh'],
            'rt' => $data['rt'],
            'rw' => $data['rw'],
            'alamat_lengkap' => $data['alamat_lengkap'],
        ]);

        if ($profil->status_verifikasi === StatusVerifikasiProfil::Perbaikan) {
            $profil->status_verifikasi = StatusVerifikasiProfil::Menunggu;
            $profil->catatan = null;
        }

        $profil->save();
        auth()->user()->update(['no_hp' => $data['no_hp'], 'email' => $data['email'] ?: null]);

        if ($request->file('foto_ktp')) {
            if ($profil->foto_ktp_path) {
                $berkas->hapus($profil->foto_ktp_path);
            }
            $file = $request->file('foto_ktp');
            $profil->update(['foto_ktp_path' => $berkas->simpanUnggahan($file, 'ktp/'.$profil->id.'.'.$file->extension())]);
        }

        if ($request->file('foto_selfie')) {
            $file = $request->file('foto_selfie');
            $profil->update(['foto_selfie_path' => $berkas->simpanUnggahan($file, 'selfie/'.$profil->id.'.'.$file->extension())]);
        }

        return back()->with('status', 'Profil diperbarui.');
    }
}
