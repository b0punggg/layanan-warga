<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BerkasPrivat
{
    public function simpan(string $isi, string $nama): string
    {
        $path = ltrim($nama, '/');

        Storage::disk('berkas')->put($path, Crypt::encryptString($isi));

        return $path;
    }

    public function baca(string $path): string
    {
        $tersimpan = Storage::disk('berkas')->get($path);

        if (! is_string($tersimpan)) {
            throw new RuntimeException('Berkas tidak ditemukan.');
        }

        return Crypt::decryptString($tersimpan);
    }

    public function simpanUnggahan(\Illuminate\Http\UploadedFile $berkas, string $nama): string
    {
        return $this->simpan($berkas->getContent(), $nama);
    }

    public function hapus(string $path): void
    {
        Storage::disk('berkas')->delete($path);
    }
}
