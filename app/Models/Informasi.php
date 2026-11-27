<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'judul', 'ringkasan', 'isi', 'terbit', 'diterbitkan_pada'])]
class Informasi extends Model
{
    protected $table = 'informasi';

    protected function casts(): array
    {
        return [
            'terbit' => 'boolean',
            'diterbitkan_pada' => 'datetime',
        ];
    }

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
