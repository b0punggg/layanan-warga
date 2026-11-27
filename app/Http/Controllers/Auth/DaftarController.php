<?php

namespace App\Http\Controllers\Auth;

use App\Enums\StatusAkun;
use App\Enums\StatusVerifikasiProfil;
use App\Http\Controllers\Controller;
use App\Models\OtpPendaftaran;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PembacaKtp;
use App\Services\PendaftaranWarga;
use App\Services\PengirimPesan;
use App\Services\PengisiPendaftaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class DaftarController extends Controller
{
    public function buat(): View
    {
        return view('auth.daftar');
    }

    public function mulai(Request $request, PendaftaranWarga $pendaftaran, PengirimPesan $pesan): RedirectResponse
    {
        $data = $request->validate([
            'nik' => ['required', 'digits:16'],
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['required', 'regex:/^08\d{8,12}$/'],
            'setuju_data' => ['accepted'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus 16 angka.',
            'nama.required' => 'Nama lengkap wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Nomor HP harus diawali 08 dan berisi 10 sampai 14 angka.',
            'setuju_data.accepted' => 'Centang persetujuan data pribadi.',
        ]);

        if (User::query()->where('nik', $data['nik'])->exists()) {
            return back()->withInput()->withErrors(['nik' => 'NIK ini sudah terdaftar. Silakan masuk.']);
        }

        $kunci = 'daftar:'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunci, 8)) {
            return back()->withInput()->withErrors(['nik' => 'Terlalu banyak percobaan. Coba lagi nanti.']);
        }

        RateLimiter::hit($kunci, 600);

        $cocok = $pendaftaran->cocok($data['nik'], $data['nama']);
        $kode = (string) random_int(100000, 999999);

        $otp = OtpPendaftaran::query()->create([
            'nik' => $data['nik'],
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'],
            'cocok_penduduk' => $cocok,
            'kode_hash' => Hash::make($kode),
            'kedaluwarsa_pada' => now()->addMinutes(5),
        ]);

        $request->session()->put('pendaftaran_otp', $otp->id);

        if (app()->environment(['local', 'testing'])) {
            $request->session()->put('otp_pengembangan', $kode);
        }

        $statusKirim = $pesan->keNomor(
            $data['no_hp'],
            'Kode pendaftaran Layanan Surat dari WhatsApp '.config('surat.notifikasi.nomor_whatsapp').': '.$kode.'. Berlaku 5 menit. Jangan berikan kode ini kepada siapa pun.',
            'Kode pendaftaran dikirim ke nomor HP dari WhatsApp '.config('surat.notifikasi.nomor_whatsapp').'.',
        );

        $pesan = $cocok
            ? 'Data cocok dengan catatan kalurahan.'
            : 'Data tidak ditemukan. Operator akan memeriksa permohonan secara manual.';
        $nomorWa = (string) config('surat.notifikasi.nomor_whatsapp');
        $kiriman = match (true) {
            $statusKirim === 'terkirim' => ' Masukkan kode yang sudah dikirim ke nomor HP Anda dari WhatsApp '.$nomorWa.'.',
            config('surat.notifikasi.kanal') !== 'whatsapp' => ' Masukkan kode yang dikirim ke nomor HP Anda.',
            (string) config('surat.notifikasi.whatsapp_url') === '' => ' Kode belum terkirim lewat WhatsApp karena alamat gateway belum diisi.',
            default => ' Kode belum terkirim. Gateway WhatsApp tidak dapat dihubungi.',
        };

        return redirect()->route('daftar.otp')->with('status', $pesan.$kiriman);
    }

    public function otp(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('pendaftaran_otp')) {
            return redirect()->route('daftar');
        }

        return view('auth.otp', [
            'kodePengembangan' => $request->session()->get('otp_pengembangan'),
        ]);
    }

    public function periksaOtp(Request $request): RedirectResponse
    {
        $data = $request->validate(['kode' => ['required', 'digits:6']], [
            'kode.required' => 'Kode wajib diisi.',
            'kode.digits' => 'Kode harus 6 angka.',
        ]);
        $otp = OtpPendaftaran::query()->find($request->session()->get('pendaftaran_otp'));

        if ($otp === null || $otp->terverifikasi_pada !== null) {
            return redirect()->route('daftar');
        }

        if ($otp->kedaluwarsa_pada->isPast()) {
            return back()->withErrors(['kode' => 'Kode sudah kedaluwarsa. Daftar ulang.']);
        }

        if ($otp->percobaan >= 5 || ! Hash::check($data['kode'], $otp->kode_hash)) {
            $otp->increment('percobaan');

            return back()->withErrors(['kode' => 'Kode tidak sesuai.']);
        }

        $otp->update(['terverifikasi_pada' => now()]);
        $request->session()->forget('otp_pengembangan');
        $request->session()->put('pendaftaran_siap', $otp->id);

        return redirect()->route('daftar.profil');
    }

    public function profil(Request $request): View|RedirectResponse
    {
        $otp = $this->otpSiap($request);

        if ($otp === null) {
            return redirect()->route('daftar');
        }

        return view('auth.profil', [
            'otp' => $otp,
            'saran' => app(PengisiPendaftaran::class)->saran($otp),
        ]);
    }

    public function baca(Request $request, PembacaKtp $pembaca, PengisiPendaftaran $pengisi): JsonResponse
    {
        $otp = $this->otpSiap($request);

        if ($otp === null) {
            return response()->json(['peringatan' => 'Sesi pendaftaran habis. Daftar ulang.', 'isian' => []], 422);
        }

        $valid = validator($request->all(), [
            'foto_ktp' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], $this->pesanProfil());

        if ($valid->fails()) {
            return response()->json([
                'peringatan' => $valid->errors()->first('foto_ktp'),
                'isian' => [],
            ], 422);
        }

        $teks = $pembaca->teks($request->file('foto_ktp')->getPathname());
        $urai = $teks !== null ? $pembaca->uraikan($teks) : [];

        if (! isset($urai['nik'])) {
            return response()->json([
                'peringatan' => 'NIK pada foto belum terbaca. Periksa isian yang sudah terisi, lalu kirim.',
                'isian' => [],
            ]);
        }

        if ($urai['nik'] !== $otp->nik) {
            return response()->json([
                'peringatan' => 'NIK pada foto tidak sama dengan NIK pendaftaran. Isi dari foto tidak dipakai.',
                'isian' => [],
            ]);
        }

        $saran = $pengisi->saran($otp);
        $isian = array_filter([
            'tempat_lahir' => $urai['tempat_lahir'] ?? null,
            'agama' => $urai['agama'] ?? null,
            'status_kawin' => $urai['status_kawin'] ?? null,
            'pekerjaan' => $urai['pekerjaan'] ?? null,
            'alamat_lengkap' => $saran['cocok'] ? null : ($urai['alamat_lengkap'] ?? null),
        ]);

        return response()->json([
            'peringatan' => $isian === []
                ? 'NIK pada foto cocok. Isian lain belum terbaca jelas.'
                : 'Sebagian isian terisi dari foto. Periksa lagi sebelum dikirim.',
            'isian' => $isian,
        ]);
    }

    public function simpan(Request $request, PendaftaranWarga $pendaftaran): RedirectResponse
    {
        $otp = $this->otpSiap($request);

        if ($otp === null) {
            return redirect()->route('daftar');
        }

        $data = $request->validate($this->aturanProfil(), $this->pesanProfil());
        $data = app(PengisiPendaftaran::class)->lengkapi($otp, $data);

        if (trim((string) ($data['alamat_lengkap'] ?? '')) === '') {
            return back()->withInput()->withErrors(['alamat_lengkap' => 'Alamat lengkap wajib diisi.']);
        }

        $langsung = $this->ktpCocok($otp, $request->file('foto_ktp'));

        $user = $pendaftaran->simpan(
            [...$data, 'nik' => $otp->nik, 'nama' => $otp->nama, 'no_hp' => $otp->no_hp],
            $request->file('foto_ktp'),
            $request->file('foto_selfie'),
            $langsung ? StatusVerifikasiProfil::Disetujui : StatusVerifikasiProfil::Menunggu,
            $langsung ? StatusAkun::Aktif : StatusAkun::Menunggu,
            null,
        );

        if ($langsung) {
            $user->profilWarga?->update(['tanggal_verifikasi' => now()]);
        }

        $request->session()->forget(['pendaftaran_otp', 'pendaftaran_siap']);

        AuditLogger::catat('daftar_warga', 'pengguna:'.$user->id, [
            'cocok' => $otp->cocok_penduduk,
            'langsung_aktif' => $langsung,
        ], $user->id);

        $status = $langsung
            ? 'Pendaftaran berhasil. Akun sudah aktif. Silakan masuk.'
            : 'Pendaftaran tersimpan. Akun menunggu pemeriksaan operator. Kabar persetujuan dikirim ke WhatsApp Anda.';

        return redirect()->route('masuk')->with('status', $status);
    }

    private function ktpCocok(OtpPendaftaran $otp, UploadedFile $foto): bool
    {
        if (! $otp->cocok_penduduk) {
            return false;
        }

        $pembaca = app(PembacaKtp::class);
        $teks = $pembaca->teks($foto->getPathname());

        if ($teks === null) {
            return false;
        }

        return ($pembaca->uraikan($teks)['nik'] ?? null) === $otp->nik;
    }

    /**
     * @return array<string, string>
     */
    private function pesanProfil(): array
    {
        return [
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Ulangan kata sandi tidak sama.',
            'foto_ktp.required' => 'Foto KTP wajib diunggah.',
            'foto_ktp.mimes' => 'Foto KTP harus JPG atau PNG.',
            'foto_ktp.max' => 'Foto KTP paling besar 2 MB.',
            'foto_ktp.uploaded' => 'Foto KTP gagal diunggah. Coba berkas yang lebih kecil.',
            'foto_selfie.mimes' => 'Swafoto harus JPG atau PNG.',
            'foto_selfie.max' => 'Swafoto paling besar 2 MB.',
            'tanggal_lahir.date' => 'Tanggal lahir bukan tanggal yang benar.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan.',
            'alamat_lengkap.max' => 'Alamat terlalu panjang.',
            'email.email' => 'Email harus berupa alamat email yang benar.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function aturanProfil(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'no_kk' => ['nullable', 'digits:16'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'jenis_kelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'agama' => ['nullable', 'string', 'max:30'],
            'status_kawin' => ['nullable', 'string', 'max:30'],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'dukuh' => ['nullable', 'string', 'max:100'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'alamat_lengkap' => ['nullable', 'string', 'max:500'],
            'foto_ktp' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'foto_selfie' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    private function otpSiap(Request $request): ?OtpPendaftaran
    {
        $otp = OtpPendaftaran::query()->find($request->session()->get('pendaftaran_siap'));

        if ($otp === null || $otp->terverifikasi_pada === null) {
            return null;
        }

        return $otp;
    }
}
