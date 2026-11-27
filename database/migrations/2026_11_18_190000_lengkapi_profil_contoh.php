<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $userId = DB::table('users')->where('nik', '3400000000000001')->value('id');

        if ($userId === null) {
            return;
        }

        $profil = DB::table('profil_warga')->where('user_id', $userId)->first();

        if ($profil === null) {
            return;
        }

        $lengkap = [
            'tempat_lahir' => 'Sleman',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Perempuan',
            'agama' => 'Islam',
            'pekerjaan' => 'Wiraswasta',
        ];
        $isi = [];

        foreach ($lengkap as $kolom => $nilai) {
            if ($profil->{$kolom} === null || $profil->{$kolom} === '') {
                $isi[$kolom] = $nilai;
            }
        }

        if ($isi !== []) {
            DB::table('profil_warga')->where('id', $profil->id)->update($isi);
        }
    }

    public function down(): void
    {
        //
    }
};
