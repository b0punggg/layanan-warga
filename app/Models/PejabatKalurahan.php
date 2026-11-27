<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'nama', 'jabatan', 'file_ttd', 'aktif'])]
class PejabatKalurahan extends Model
{
    protected $table = 'pejabat_kalurahan';

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class, 'penandatangan_id');
    }
}
