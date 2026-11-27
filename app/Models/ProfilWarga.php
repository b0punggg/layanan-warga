<?php

namespace App\Models;

use App\Enums\StatusVerifikasiProfil;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'penduduk_id',
    'user_id',
    'no_kk',
    'tempat_lahir',
    'tanggal_lahir',
    'jenis_kelamin',
    'agama',
    'status_kawin',
    'pekerjaan',
    'dukuh',
    'rt',
    'rw',
    'alamat_lengkap',
    'no_hp',
    'email',
    'foto_ktp_path',
    'foto_selfie_path',
    'status_verifikasi',
    'diverifikasi_oleh',
    'tanggal_verifikasi',
    'catatan',
])]
class ProfilWarga extends Model
{
    protected $table = 'profil_warga';

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_verifikasi' => 'datetime',
            'status_verifikasi' => StatusVerifikasiProfil::class,
        ];
    }

    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }
}
