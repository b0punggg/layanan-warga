<?php

namespace App\Models;

use App\Enums\StatusDataPenduduk;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'nik',
    'nama',
    'alamat_asli',
    'dukuh',
    'rt',
    'rw',
    'status_data',
    'sumber_data',
    'tanggal_impor',
    'riwayat_impor_id',
])]
class Penduduk extends Model
{
    protected $table = 'penduduk';

    protected function casts(): array
    {
        return [
            'status_data' => StatusDataPenduduk::class,
            'tanggal_impor' => 'date',
        ];
    }

    public function profilWarga(): HasOne
    {
        return $this->hasOne(ProfilWarga::class);
    }

    public function riwayatImpor(): BelongsTo
    {
        return $this->belongsTo(RiwayatImpor::class);
    }
}
