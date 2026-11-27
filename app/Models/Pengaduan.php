<?php

namespace App\Models;

use App\Enums\StatusPengaduan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'nama',
    'no_hp',
    'email',
    'judul',
    'isi',
    'status',
    'tanggapan',
    'ditanggapi_oleh',
    'ditanggapi_pada',
])]
class Pengaduan extends Model
{
    protected $table = 'pengaduan';

    protected function casts(): array
    {
        return [
            'status' => StatusPengaduan::class,
            'ditanggapi_pada' => 'datetime',
        ];
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditanggapi_oleh');
    }
}
