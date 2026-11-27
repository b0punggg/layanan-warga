<?php

namespace App\Services;

class Wilayah
{
    public static function kode(?string $nilai): ?string
    {
        $angka = preg_replace('/\D/', '', (string) $nilai) ?? '';

        if ($angka === '') {
            return null;
        }

        return str_pad(substr($angka, -3), 3, '0', STR_PAD_LEFT);
    }
}
