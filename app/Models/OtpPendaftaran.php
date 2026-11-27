<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nik',
    'nama',
    'no_hp',
    'cocok_penduduk',
    'kode_hash',
    'percobaan',
    'kedaluwarsa_pada',
    'terverifikasi_pada',
])]
class OtpPendaftaran extends Model
{
    protected $table = 'otp_pendaftaran';

    protected function casts(): array
    {
        return [
            'cocok_penduduk' => 'boolean',
            'kedaluwarsa_pada' => 'datetime',
            'terverifikasi_pada' => 'datetime',
        ];
    }
}
