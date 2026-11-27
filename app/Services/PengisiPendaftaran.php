<?php

namespace App\Services;

use App\Models\OtpPendaftaran;
use App\Models\Penduduk;

class PengisiPendaftaran
{
    public function __construct(
        private PembacaNik $nik,
        private PenguraiAlamat $pengurai,
    ) {}

    /**
     * @return array{cocok: bool, nama: string, nik: string, alamat_lengkap: string, dukuh: ?string, rt: ?string, rw: ?string, tanggal_lahir: ?string, jenis_kelamin: ?string}
     */
    public function saran(OtpPendaftaran $otp): array
    {
        $penduduk = Penduduk::query()->where('nik', $otp->nik)->first();
        $cocok = (bool) $otp->cocok_penduduk && $penduduk !== null;
        $alamat = $cocok ? (string) $penduduk->alamat_asli : '';
        $urai = $alamat !== ''
            ? $this->pengurai->uraikan($alamat)
            : ['dukuh' => null, 'rt' => null, 'rw' => null, 'berhasil' => false];
        $dariNik = $this->nik->baca($otp->nik);

        return [
            'cocok' => $cocok,
            'nama' => $otp->nama,
            'nik' => $otp->nik,
            'alamat_lengkap' => $alamat,
            'dukuh' => $cocok ? ($penduduk->dukuh ?: $urai['dukuh']) : null,
            'rt' => $cocok ? ($penduduk->rt ?: $urai['rt']) : null,
            'rw' => $cocok ? ($penduduk->rw ?: $urai['rw']) : null,
            'tanggal_lahir' => $dariNik['tanggal_lahir'],
            'jenis_kelamin' => $dariNik['jenis_kelamin'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function lengkapi(OtpPendaftaran $otp, array $data): array
    {
        $saran = $this->saran($otp);
        $alamat = trim((string) ($data['alamat_lengkap'] ?? ''));

        if ($alamat === '' && $saran['cocok']) {
            $alamat = $saran['alamat_lengkap'];
        }

        $data['alamat_lengkap'] = $alamat;
        $urai = $this->pengurai->uraikan($alamat);
        $sama = $saran['cocok'] && $alamat === $saran['alamat_lengkap'];

        foreach (['dukuh', 'rt', 'rw'] as $kunci) {
            $isi = trim((string) ($data[$kunci] ?? ''));

            if ($isi !== '') {
                $data[$kunci] = $isi;

                continue;
            }

            if ($urai['berhasil'] && $urai[$kunci]) {
                $data[$kunci] = $urai[$kunci];

                continue;
            }

            $data[$kunci] = $sama ? $saran[$kunci] : null;
        }

        if (trim((string) ($data['tanggal_lahir'] ?? '')) === '') {
            $data['tanggal_lahir'] = $saran['tanggal_lahir'];
        }

        if (trim((string) ($data['jenis_kelamin'] ?? '')) === '') {
            $data['jenis_kelamin'] = $saran['jenis_kelamin'];
        }

        return $data;
    }
}
