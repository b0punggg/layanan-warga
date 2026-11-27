<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pengajuan_id', 'status_lama', 'status_baru', 'catatan', 'user_id', 'waktu'])]
class RiwayatStatus extends Model
{
    public $timestamps = false;

    protected $table = 'riwayat_status';

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
