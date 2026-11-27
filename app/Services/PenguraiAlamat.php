<?php

namespace App\Services;

class PenguraiAlamat
{
    /**
     * @return array{dukuh: ?string, rt: ?string, rw: ?string, berhasil: bool}
     */
    public function uraikan(string $alamat): array
    {
        $alamat = trim((string) preg_replace('/\s+/u', ' ', $alamat));
        $rt = null;
        $rw = null;
        $dukuh = null;

        if (preg_match('/RT\.?\s*0*(\d{1,3})\s*(?:\/|,|\s)\s*RW\.?\s*0*(\d{1,3})/iu', $alamat, $cocok) === 1) {
            $rt = str_pad($cocok[1], 3, '0', STR_PAD_LEFT);
            $rw = str_pad($cocok[2], 3, '0', STR_PAD_LEFT);
            $sisa = trim((string) preg_replace('/RT\.?\s*0*\d{1,3}.*/iu', '', $alamat));
            $sisa = trim($sisa, " \t,.-");
            $dukuh = $sisa !== '' ? $sisa : null;
        }

        return [
            'dukuh' => $dukuh,
            'rt' => $rt,
            'rw' => $rw,
            'berhasil' => $dukuh !== null && $rt !== null && $rw !== null,
        ];
    }
}
