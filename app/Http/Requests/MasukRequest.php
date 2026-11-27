<?php

namespace App\Http\Requests;

use App\Enums\StatusAkun;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Isi email atau NIK.',
            'password.required' => 'Isi kata sandi.',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim($this->string('login')->toString());
        $field = preg_match('/^\d{16}$/', $login) === 1 ? 'nik' : 'email';

        $user = User::query()->where($field, $login)->first();

        if ($user !== null && ! in_array($user->status_akun, [StatusAkun::Aktif, StatusAkun::Menunggu], true)) {
            throw ValidationException::withMessages([
                'login' => 'Akun ini belum aktif. Hubungi operator kalurahan.',
            ]);
        }

        if (! Auth::attempt(
            [$field => $login, 'password' => $this->string('password')->toString()],
            $this->boolean('ingat'),
        )) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Email, NIK, atau kata sandi tidak sesuai.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $menit = (int) ceil(RateLimiter::availableIn($this->throttleKey()) / 60);

        throw ValidationException::withMessages([
            'login' => "Terlalu banyak percobaan. Coba lagi dalam {$menit} menit.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower(trim($this->string('login')->toString())).'|'.$this->ip());
    }
}
