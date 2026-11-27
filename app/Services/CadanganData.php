<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class CadanganData
{
    public function buat(?string $tujuan = null): string
    {
        $this->pastikanMysql();
        $folder = $tujuan ?: storage_path('app/private/cadangan/'.now()->format('Ymd-His'));
        File::ensureDirectoryExists($folder);

        $dump = $folder.DIRECTORY_SEPARATOR.'basis.sql';
        $this->jalankan(array_merge($this->perintahKlien('mysqldump'), [
            '--single-transaction',
            '--routines',
            '--default-character-set=utf8mb4',
            '--no-tablespaces',
            '--result-file='.$dump,
            $this->namaBasisAktif(),
        ]));

        if (! is_file($dump) || filesize($dump) < 1) {
            throw new RuntimeException('Berkas cadangan basis data kosong.');
        }

        $berkas = storage_path('app/private/berkas');
        $salinanBerkas = $folder.DIRECTORY_SEPARATOR.'berkas';
        $jumlahBerkas = 0;
        if (is_dir($berkas)) {
            File::copyDirectory($berkas, $salinanBerkas);
            $jumlahBerkas = count(File::allFiles($salinanBerkas));
        }

        File::put($folder.DIRECTORY_SEPARATOR.'manifest.json', json_encode([
            'dibuat' => now()->toIso8601String(),
            'basis' => $this->namaBasisAktif(),
            'ukuran_sql' => filesize($dump),
            'jumlah_berkas' => $jumlahBerkas,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $folder;
    }

    public function pulihkan(string $folder, string $basis): void
    {
        if (! $this->namaBasisAman($basis)) {
            throw new RuntimeException('Nama basis data hanya boleh huruf, angka, dan garis bawah.');
        }

        $this->pastikanMysql();

        $dump = rtrim($folder, '\\/').DIRECTORY_SEPARATOR.'basis.sql';
        if (! is_file($dump)) {
            throw new RuntimeException('Berkas basis.sql tidak ada di folder cadangan.');
        }

        $this->jalankan(array_merge($this->perintahKlien('mysql'), [
            '-e',
            'CREATE DATABASE IF NOT EXISTS `'.$basis.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        ]));

        $masuk = fopen($dump, 'rb');
        if ($masuk === false) {
            throw new RuntimeException('Berkas cadangan tidak bisa dibaca.');
        }

        try {
            $this->jalankan(array_merge($this->perintahKlien('mysql'), [
                '--default-character-set=utf8mb4',
                $basis,
            ]), $masuk);
        } finally {
            fclose($masuk);
        }
    }

    public function namaBasisAman(string $nama): bool
    {
        return preg_match('/^[A-Za-z0-9_]+$/', $nama) === 1;
    }

    private function pastikanMysql(): void
    {
        if (config('database.default') !== 'mysql') {
            throw new RuntimeException('Cadangan ini hanya untuk koneksi MySQL.');
        }
    }

    private function namaBasisAktif(): string
    {
        $nama = (string) config('database.connections.mysql.database');
        if (! $this->namaBasisAman($nama)) {
            throw new RuntimeException('Nama basis data aktif tidak aman untuk dicadangkan.');
        }

        return $nama;
    }

    /**
     * @return list<string>
     */
    private function perintahKlien(string $nama): array
    {
        $perintah = [
            $this->biner($nama),
            '-h', (string) config('database.connections.mysql.host'),
            '-P', (string) config('database.connections.mysql.port'),
            '-u', (string) config('database.connections.mysql.username'),
        ];
        $sandi = (string) config('database.connections.mysql.password');
        if ($sandi !== '') {
            $perintah[] = '-p'.$sandi;
        }

        return $perintah;
    }

    private function biner(string $nama): string
    {
        $dariEnv = env($nama === 'mysqldump' ? 'MYSQLDUMP_PATH' : 'MYSQL_PATH');
        if (is_string($dariEnv) && is_file($dariEnv)) {
            return $dariEnv;
        }

        $ketemu = glob('C:\\laragon\\bin\\mysql\\mysql-*\\bin\\'.$nama.'.exe') ?: [];
        if ($ketemu !== []) {
            return $ketemu[0];
        }

        if ($this->adaDiPath($nama)) {
            return $nama;
        }

        throw new RuntimeException($nama.' tidak ditemukan. Atur MYSQLDUMP_PATH dan MYSQL_PATH.');
    }

    private function adaDiPath(string $nama): bool
    {
        $proses = new Process([$nama, '--version']);
        $proses->run();

        return $proses->isSuccessful();
    }

    /**
     * @param  list<string>  $perintah
     * @param  resource|null  $masukan
     */
    private function jalankan(array $perintah, $masukan = null): void
    {
        $proses = new Process($perintah);
        $proses->setTimeout(300);
        if (is_resource($masukan)) {
            $proses->setInput($masukan);
        }
        $proses->run();

        if (! $proses->isSuccessful()) {
            throw new RuntimeException(trim($proses->getErrorOutput()) ?: 'Perintah MySQL gagal.');
        }
    }
}
