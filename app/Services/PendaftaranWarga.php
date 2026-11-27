<?php

namespace App\Services;

use App\Enums\StatusAkun;
use App\Enums\StatusDataPenduduk;
use App\Enums\StatusVerifikasiProfil;
use App\Models\Penduduk;
use App\Models\ProfilWarga;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PendaftaranWarga
{
    public function cocok(string $nik, string $nama): bool
    {
        $penduduk = Penduduk::query()->where('nik', $nik)->first();

        if ($penduduk === null) {
            return false;
        }

        return mb_strtolower(trim($penduduk->nama)) === mb_strtolower(trim($nama));
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    public function simpan(
        array $atribut,
        ?UploadedFile $ktp,
        ?UploadedFile $selfie,
        StatusVerifikasiProfil $verifikasi,
        StatusAkun $akun,
        ?User $pemeriksa,
    ): User {
        return DB::transaction(function () use ($atribut, $ktp, $selfie, $verifikasi, $akun, $pemeriksa) {
            $isi = fn (string $kunci) => ($atribut[$kunci] ?? null) ?: null;

            $penduduk = Penduduk::query()->firstOrCreate(
                ['nik' => $atribut['nik']],
                [
                    'nama' => $atribut['nama'],
                    'alamat_asli' => $isi('alamat_lengkap') ?? 'Belum diisi',
                    'status_data' => StatusDataPenduduk::Menunggu,
                    'sumber_data' => 'pendaftaran',
                ],
            );

            $user = User::query()->create([
                'name' => $atribut['nama'],
                'email' => $isi('email'),
                'no_hp' => $atribut['no_hp'],
                'nik' => $atribut['nik'],
                'peran' => 'warga',
                'status_akun' => $akun,
                'password' => $atribut['password'],
            ]);

            $profil = ProfilWarga::query()->create([
                'penduduk_id' => $penduduk->id,
                'user_id' => $user->id,
                'no_kk' => $isi('no_kk'),
                'tempat_lahir' => $isi('tempat_lahir'),
                'tanggal_lahir' => $isi('tanggal_lahir'),
                'jenis_kelamin' => $isi('jenis_kelamin'),
                'agama' => $isi('agama'),
                'status_kawin' => $isi('status_kawin'),
                'pekerjaan' => $isi('pekerjaan'),
                'dukuh' => $isi('dukuh'),
                'rt' => $isi('rt'),
                'rw' => $isi('rw'),
                'alamat_lengkap' => $isi('alamat_lengkap'),
                'no_hp' => $atribut['no_hp'],
                'email' => $isi('email'),
                'status_verifikasi' => $verifikasi,
                'diverifikasi_oleh' => $pemeriksa?->id,
                'tanggal_verifikasi' => $pemeriksa ? now() : null,
            ]);

            $berkas = app(BerkasPrivat::class);

            if ($ktp !== null) {
                $profil->update([
                    'foto_ktp_path' => $berkas->simpanUnggahan($ktp, 'ktp/'.$profil->id.'.'.$ktp->extension()),
                ]);
            }

            if ($selfie !== null) {
                $profil->update([
                    'foto_selfie_path' => $berkas->simpanUnggahan($selfie, 'selfie/'.$profil->id.'.'.$selfie->extension()),
                ]);
            }

            return $user;
        });
    }
}
