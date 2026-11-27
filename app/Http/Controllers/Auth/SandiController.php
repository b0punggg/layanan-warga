<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PengirimPesan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class SandiController extends Controller
{
    public function minta(): View
    {
        return view('auth.lupa');
    }

    public function kirim(Request $request, PengirimPesan $pesan): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Email harus berupa alamat email yang benar.',
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if ($user !== null && ! Password::broker()->getRepository()->recentlyCreatedToken($user)) {
            $token = Password::broker()->createToken($user);
            $tautan = route('password.reset', ['token' => $token, 'email' => $user->email]);
            $isi = 'Tautan atur ulang kata sandi Layanan Surat: '.$tautan.'. Berlaku 60 menit. Abaikan jika Anda tidak meminta ini.';

            $pesan->keAkun($user, $isi, 'Tautan atur ulang kata sandi dikirim.', false);
            $this->kirimEmail($user, $isi);
        }

        return back()->with('status', 'Jika email terdaftar, tautan pengaturan kata sandi dikirim ke email itu dan ke WhatsApp nomor akun tersebut.');
    }

    private function kirimEmail(User $user, string $isi): void
    {
        if (! filled($user->email)) {
            return;
        }

        try {
            Mail::raw($isi, function ($pesan) use ($user): void {
                $pesan->to($user->email)->subject('Atur ulang kata sandi Layanan Surat');
            });
        } catch (Throwable) {
        }
    }

    public function atur(string $token): View
    {
        return view('auth.atur-sandi', ['token' => $token, 'email' => request('email')]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => $password])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Tautan tidak berlaku. Minta tautan baru.']);
        }

        return redirect()->route('masuk')->with('status', 'Kata sandi diperbarui. Silakan masuk.');
    }
}
