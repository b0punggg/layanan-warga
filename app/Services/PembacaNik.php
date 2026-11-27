<?php

namespace App\Services;

class PembacaNik
{
    /**
     * Tanggal lahir dan jenis kelamin dari 16 digit NIK.
     * Tanggal perempuan disimpan dengan hari ditambah 40.
     *
     * @return array{tanggal_lahir: ?string, jenis_kelamin: ?string}
     */
    public function baca(string $nik): array
    {
        $kosong = ['tanggal_lahir' => null, 'jenis_kelamin' => null];

        if (preg_match('/^\d{16}$/', $nik) !== 1) {
            return $kosong;
        }

        $hari = (int) substr($nik, 6, 2);
        $bulan = (int) substr($nik, 8, 2);
        $tahunDua = (int) substr($nik, 10, 2);
        $perempuan = $hari > 40;

        if ($perempuan) {
            $hari -= 40;
        }

        if ($bulan < 1 || $bulan > 12 || $hari < 1 || $hari > 31) {
            return $kosong;
        }

        $tahun = 2000 + $tahunDua;
        if (! checkdate($bulan, $hari, $tahun)) {
            return $kosong;
        }

        $tanggal = sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari);

        if ($tanggal > now()->toDateString()) {
            $tahun -= 100;

            if (! checkdate($bulan, $hari, $tahun)) {
                return $kosong;
            }

            $tanggal = sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari);
        }

        return [
            'tanggal_lahir' => $tanggal,
            'jenis_kelamin' => $perempuan ? 'Perempuan' : 'Laki-laki',
        ];
    }
}
