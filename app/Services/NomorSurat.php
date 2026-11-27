<?php

namespace App\Services;

use Carbon\CarbonInterface;
use InvalidArgumentException;

class NomorSurat
{
    public function susun(string $kode, int $nomorUrut, CarbonInterface $tanggal, ?string $format = null): string
    {
        if ($nomorUrut < 1) {
            throw new InvalidArgumentException('Nomor urut dimulai dari 1.');
        }

        $format ??= (string) config('surat.format');

        $bulan = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$tanggal->month - 1];

        return strtr($format, [
            '{kode}' => $kode,
            '{nomor}' => str_pad((string) $nomorUrut, 3, '0', STR_PAD_LEFT),
            '{bulan_romawi}' => $bulan,
            '{tahun}' => (string) $tanggal->year,
        ]);
    }

    public function urutDariTeks(string $nomor, ?string $format = null): ?int
    {
        $format ??= (string) config('surat.format');
        $bagian = explode('/', $nomor);
        $pola = explode('/', $format);

        foreach ($pola as $indeks => $potong) {
            if (! str_contains($potong, '{nomor}') || ! isset($bagian[$indeks]) || ! ctype_digit($bagian[$indeks])) {
                continue;
            }

            $urut = (int) $bagian[$indeks];

            return $urut >= 1 ? $urut : null;
        }

        for ($indeks = count($bagian) - 1; $indeks >= 0; $indeks--) {
            if (! ctype_digit($bagian[$indeks])) {
                continue;
            }

            $urut = (int) $bagian[$indeks];

            if ($urut >= 1900 && $urut <= 2100 && strlen($bagian[$indeks]) === 4) {
                continue;
            }

            return $urut >= 1 ? $urut : null;
        }

        return null;
    }
}
