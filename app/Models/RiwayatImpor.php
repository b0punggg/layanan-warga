<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama_file', 'user_id', 'waktu', 'baris_total', 'baris_sukses', 'baris_gagal', 'laporan_gagal_path'])]
class RiwayatImpor extends Model
{
    public $timestamps = false;

    protected $table = 'riwayat_impor';

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penduduk(): HasMany
    {
        return $this->hasMany(Penduduk::class);
    }
}
