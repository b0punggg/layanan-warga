<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('jenis_layanan')->get() as $item) {
            $syarat = json_decode((string) $item->daftar_syarat, true) ?? [];
            $ubahSyarat = false;

            foreach ($syarat as &$baris) {
                if (($baris['nama'] ?? '') === 'kk' && ($baris['wajib'] ?? false)) {
                    $baris['wajib'] = false;
                    $ubahSyarat = true;
                }
            }

            $data = [];

            if ($ubahSyarat) {
                $data['daftar_syarat'] = json_encode(array_values($syarat));
            }

            if ($item->butuh_pengantar_rt && $item->mode_pengantar === 'unggah') {
                $data['butuh_pengantar_rt'] = false;
            }

            if ($data !== []) {
                DB::table('jenis_layanan')->where('id', $item->id)->update($data);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
