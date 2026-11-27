<?php

namespace App\Console\Commands;

use App\Services\CadanganData;
use Illuminate\Console\Command;
use RuntimeException;

class Pulihkan extends Command
{
    protected $signature = 'layanan:pulihkan {folder : Folder hasil layanan:cadangkan} {--basis= : Nama basis data tujuan} {--timpa : Izinkan menimpa basis data yang sedang dipakai aplikasi}';

    protected $description = 'Pulihkan basis data dari folder cadangan';

    public function handle(CadanganData $cadangan): int
    {
        $basis = (string) ($this->option('basis') ?: '');
        if ($basis === '') {
            $this->error('Isi --basis=nama_basis_tujuan.');

            return self::FAILURE;
        }

        $aktif = (string) config('database.connections.mysql.database');
        if ($basis === $aktif && ! $this->option('timpa')) {
            $this->error('Basis ini sedang dipakai. Tambahkan --timpa hanya jika pemulihan ini memang disengaja.');

            return self::FAILURE;
        }

        try {
            $cadangan->pulihkan((string) $this->argument('folder'), $basis);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Basis data '.$basis.' dipulihkan. Berkas privat disalin manual dari folder cadangan bila diperlukan.');

        return self::SUCCESS;
    }
}
