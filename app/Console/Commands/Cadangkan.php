<?php

namespace App\Console\Commands;

use App\Services\CadanganData;
use Illuminate\Console\Command;
use RuntimeException;

class Cadangkan extends Command
{
    protected $signature = 'layanan:cadangkan {--tujuan= : Folder tujuan. Kosong berarti penyimpanan privat aplikasi}';

    protected $description = 'Cadangkan basis data MySQL dan berkas privat';

    public function handle(CadanganData $cadangan): int
    {
        try {
            $folder = $cadangan->buat($this->option('tujuan') ?: null);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Cadangan tersimpan di '.$folder);

        return self::SUCCESS;
    }
}
