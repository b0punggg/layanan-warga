<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pengajuan_id',
    'nomor_surat',
    'tahun',
    'nomor_urut',
    'file_pdf',
    'tanggal_terbit',
    'penandatangan_id',
    'kode_verifikasi',
    'tanggal_diambil',
])]
class Surat extends Model
{
    protected $table = 'surat';

    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date',
            'tanggal_diambil' => 'datetime',
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function penandatangan(): BelongsTo
    {
        return $this->belongsTo(PejabatKalurahan::class, 'penandatangan_id');
    }
}
