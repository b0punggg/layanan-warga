<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use App\Enums\StatusPengantarRt;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'kode_tiket',
    'user_id',
    'jenis_layanan_id',
    'data_form',
    'status',
    'status_pengantar_rt',
    'berkas_pengantar_path',
    'file_hasil',
    'tanggal_ajuan',
    'tanggal_selesai',
])]
class Pengajuan extends Model
{
    protected $table = 'pengajuan';

    protected function casts(): array
    {
        return [
            'data_form' => 'array',
            'status' => StatusPengajuan::class,
            'status_pengantar_rt' => StatusPengantarRt::class,
            'tanggal_ajuan' => 'datetime',
            'tanggal_selesai' => 'datetime',
        ];
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jenisLayanan(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class);
    }

    public function berkas(): HasMany
    {
        return $this->hasMany(BerkasPengajuan::class);
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatus::class);
    }

    public function surat(): HasOne
    {
        return $this->hasOne(Surat::class);
    }
}
