<?php

namespace App\Enums;

enum Peran: string
{
    case Warga = 'warga';
    case Operator = 'operator';
    case Lurah = 'lurah';
    case Admin = 'admin';
    case RtRw = 'rt_rw';

    public function label(): string
    {
        return match ($this) {
            self::Warga => 'Warga',
            self::Operator => 'Operator',
            self::Lurah => 'Lurah',
            self::Admin => 'Admin sistem',
            self::RtRw => 'RT/RW',
        };
    }
}
