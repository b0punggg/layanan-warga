<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pengajuan_id', 'nama_syarat', 'path_file', 'tipe', 'ukuran', 'diunggah_pada'])]
class BerkasPengajuan extends Model
{
    public $timestamps = false;

    protected $table = 'berkas_pengajuan';

    protected function casts(): array
    {
        return [
            'diunggah_pada' => 'datetime',
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }
}
