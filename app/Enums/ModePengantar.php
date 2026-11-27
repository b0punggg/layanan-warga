<?php

namespace App\Enums;

enum ModePengantar: string
{
    case Unggah = 'unggah';
    case VerifikasiOnline = 'verifikasi_online';

    public function label(): string
    {
        return match ($this) {
            self::Unggah => 'Unggah foto pengantar',
            self::VerifikasiOnline => 'Persetujuan RT/RW online',
        };
    }
}
