<?php

namespace App\Enums;

enum StatusPengajuan: string
{
    case Draft = 'draft';
    case Diajukan = 'diajukan';
    case PerluPerbaikan = 'perlu_perbaikan';
    case Diverifikasi = 'diverifikasi';
    case Disetujui = 'disetujui';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Diajukan => 'Diajukan',
            self::PerluPerbaikan => 'Perlu perbaikan',
            self::Diverifikasi => 'Diverifikasi',
            self::Disetujui => 'Disetujui',
            self::Selesai => 'Selesai',
            self::Ditolak => 'Ditolak',
        };
    }
}
