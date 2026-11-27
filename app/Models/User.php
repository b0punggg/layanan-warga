<?php

namespace App\Models;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Enums\StatusVerifikasiProfil;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'no_hp', 'nik', 'dukuh', 'rt', 'rw', 'peran', 'status_akun', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terakhir_login' => 'datetime',
            'password' => 'hashed',
            'peran' => Peran::class,
            'status_akun' => StatusAkun::class,
        ];
    }

    public function profilWarga(): HasOne
    {
        return $this->hasOne(ProfilWarga::class);
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }

    public function berandaRoute(): string
    {
        return match ($this->peran) {
            Peran::Warga => 'warga.beranda',
            Peran::Operator => 'operator.beranda',
            Peran::Lurah => 'lurah.beranda',
            Peran::Admin => 'admin.beranda',
            Peran::RtRw => 'rt.beranda',
        };
    }

    public function aktif(): bool
    {
        return $this->status_akun === StatusAkun::Aktif;
    }

    public function bolehMengajukan(): bool
    {
        return $this->aktif()
            && $this->profilWarga?->status_verifikasi === StatusVerifikasiProfil::Disetujui;
    }
}
