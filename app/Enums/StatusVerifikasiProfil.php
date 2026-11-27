<?php

namespace App\Enums;

enum StatusVerifikasiProfil: string
{
    case Menunggu = 'menunggu';
    case Disetujui = 'disetujui';
    case Perbaikan = 'perbaikan';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu verifikasi',
            self::Disetujui => 'Disetujui',
            self::Perbaikan => 'Perlu perbaikan',
            self::Ditolak => 'Ditolak',
        };
    }
}
