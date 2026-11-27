<?php

namespace App\Services;

use App\Models\User;

class LengkapiProfil
{
    public function __construct(private PenguraiAlamat $pengurai) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function dariSurat(User $user, array $data): void
    {
        $profil = $user->profilWarga;

        if ($profil === null) {
            return;
        }

        $ubah = [];

        foreach (['tempat_lahir', 'agama', 'status_kawin', 'pekerjaan'] as $kunci) {
            $nilai = trim((string) ($data[$kunci] ?? ''));

            if ($nilai !== '') {
                $ubah[$kunci] = $nilai;
            }
        }

        $kelamin = trim((string) ($data['jenis_kelamin'] ?? ''));

        if (in_array($kelamin, ['Laki-laki', 'Perempuan'], true)) {
            $ubah['jenis_kelamin'] = $kelamin;
        }

        $tanggal = $this->tanggal((string) ($data['tanggal_lahir'] ?? ''));

        if ($tanggal !== null) {
            $ubah['tanggal_lahir'] = $tanggal;
        }

        $alamat = trim((string) ($data['alamat'] ?? ''));

        if ($alamat !== '') {
            $ubah['alamat_lengkap'] = $alamat;
            $urai = $this->pengurai->uraikan($alamat);

            if ($urai['berhasil']) {
                if (! $profil->dukuh) {
                    $ubah['dukuh'] = $urai['dukuh'];
                }

                if (! $profil->rt) {
                    $ubah['rt'] = $urai['rt'];
                }

                if (! $profil->rw) {
                    $ubah['rw'] = $urai['rw'];
                }
            }
        }

        if ($ubah !== []) {
            $profil->update($ubah);
        }
    }

    private function tanggal(string $mentah): ?string
    {
        $mentah = trim($mentah);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $mentah, $cocok) === 1 && checkdate((int) $cocok[2], (int) $cocok[3], (int) $cocok[1])) {
            return $mentah;
        }

        if (preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/u', $mentah, $cocok) !== 1) {
            return null;
        }

        $bulan = [
            'januari' => 1, 'january' => 1,
            'februari' => 2, 'february' => 2,
            'maret' => 3, 'march' => 3,
            'april' => 4,
            'mei' => 5, 'may' => 5,
            'juni' => 6, 'june' => 6,
            'juli' => 7, 'july' => 7,
            'agustus' => 8, 'august' => 8,
            'september' => 9,
            'oktober' => 10, 'october' => 10,
            'november' => 11,
            'desember' => 12, 'december' => 12,
        ][mb_strtolower($cocok[2])] ?? null;

        if ($bulan === null || ! checkdate($bulan, (int) $cocok[1], (int) $cocok[3])) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', (int) $cocok[3], $bulan, (int) $cocok[1]);
    }
}
