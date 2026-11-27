<?php

namespace App\Enums;

enum StatusAkun: string
{
    case Menunggu = 'menunggu';
    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Aktif => 'Aktif',
            self::Nonaktif => 'Nonaktif',
            self::Ditolak => 'Ditolak',
        };
    }
}
