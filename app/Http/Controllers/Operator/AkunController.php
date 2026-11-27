<?php

namespace App\Http\Controllers\Operator;

use App\Enums\StatusAkun;
use App\Enums\StatusVerifikasiProfil;
use App\Http\Controllers\Controller;
use App\Models\ProfilWarga;
use App\Services\AuditLogger;
use App\Services\BerkasPrivat;
use App\Services\PendaftaranWarga;
use App\Services\PengirimPesan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AkunController extends Controller
{
    public function index(): View
    {
        return view('operator.akun.index', [
            'antrean' => ProfilWarga::query()
                ->with(['user', 'penduduk'])
                ->where('status_verifikasi', StatusVerifikasiProfil::Menunggu)
                ->oldest()
                ->get(),
        ]);
    }

    public function lihat(ProfilWarga $profil): View
    {
        $profil->load(['user', 'penduduk']);
        AuditLogger::catat('buka_berkas_identitas', 'profil_warga:'.$profil->id);

        return view('operator.akun.lihat', ['profil' => $profil]);
    }

    public function setujui(Request $request, ProfilWarga $profil, BerkasPrivat $berkas, PengirimPesan $pesan): RedirectResponse
    {
        $this->hapusSelfie($profil, $berkas);
        $profil->update([
            'status_verifikasi' => StatusVerifikasiProfil::Disetujui,
            'diverifikasi_oleh' => $request->user()->id,
            'tanggal_verifikasi' => now(),
            'catatan' => null,
        ]);
        $profil->user?->update(['status_akun' => StatusAkun::Aktif]);
        $this->kabari($profil, $pesan, 'Pendaftaran Layanan Surat Kalurahan Sindumartani disetujui. Anda sudah dapat masuk dan mengajukan surat.');
        AuditLogger::catat('setujui_akun', 'profil_warga:'.$profil->id);

        return redirect()->route('operator.akun.index')->with('status', 'Akun warga disetujui.');
    }

    public function perbaikan(Request $request, ProfilWarga $profil, PengirimPesan $pesan): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['required', 'string', 'max:1000']]);
        $profil->update([
            'status_verifikasi' => StatusVerifikasiProfil::Perbaikan,
            'catatan' => $data['catatan'],
        ]);
        $this->kabari($profil, $pesan, 'Pendaftaran Layanan Surat perlu diperbaiki. '.$data['catatan'].' Masuk ke akun Anda lalu perbarui profil.');
        AuditLogger::catat('minta_perbaikan_akun', 'profil_warga:'.$profil->id);

        return back()->with('status', 'Warga diminta memperbaiki data.');
    }

    public function tolak(Request $request, ProfilWarga $profil, BerkasPrivat $berkas, PengirimPesan $pesan): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['required', 'string', 'max:1000']]);
        $this->hapusSelfie($profil, $berkas);
        $profil->update([
            'status_verifikasi' => StatusVerifikasiProfil::Ditolak,
            'catatan' => $data['catatan'],
            'diverifikasi_oleh' => $request->user()->id,
            'tanggal_verifikasi' => now(),
        ]);
        $profil->user?->update(['status_akun' => StatusAkun::Ditolak]);
        $this->kabari($profil, $pesan, 'Pendaftaran Layanan Surat ditolak. Catatan operator: '.$data['catatan']);
        AuditLogger::catat('tolak_akun', 'profil_warga:'.$profil->id);

        return redirect()->route('operator.akun.index')->with('status', 'Akun ditolak.');
    }

    public function buat(): View
    {
        return view('operator.akun.buat');
    }

    public function simpan(Request $request, PendaftaranWarga $pendaftaran): RedirectResponse
    {
        $data = $request->validate([
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['required', 'regex:/^08\d{8,12}$/'],
            'password' => ['required', 'string', 'min:8'],
            'alamat_lengkap' => ['nullable', 'string', 'max:500'],
            'foto_ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $user = $pendaftaran->simpan(
            [
                ...$data,
                'email' => null,
                'no_kk' => null,
                'tempat_lahir' => null,
                'tanggal_lahir' => null,
                'jenis_kelamin' => null,
                'agama' => null,
                'status_kawin' => null,
                'pekerjaan' => null,
                'dukuh' => null,
                'rt' => null,
                'rw' => null,
            ],
            $request->file('foto_ktp'),
            null,
            StatusVerifikasiProfil::Disetujui,
            StatusAkun::Aktif,
            $request->user(),
        );

        AuditLogger::catat('daftar_warga_loket', 'pengguna:'.$user->id);

        return redirect()->route('operator.akun.index')->with('status', 'Warga '.$user->name.' terdaftar dan langsung aktif.');
    }

    private function kabari(ProfilWarga $profil, PengirimPesan $pesan, string $isi): void
    {
        $user = $profil->user;

        if ($user !== null) {
            $pesan->keAkun($user, $isi);
        }
    }

    private function hapusSelfie(ProfilWarga $profil, BerkasPrivat $berkas): void
    {
        if ($profil->foto_selfie_path) {
            $berkas->hapus($profil->foto_selfie_path);
            $profil->foto_selfie_path = null;
            $profil->save();
            AuditLogger::catat('hapus_selfie', 'profil_warga:'.$profil->id);
        }
    }
}
