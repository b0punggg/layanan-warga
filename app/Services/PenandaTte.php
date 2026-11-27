<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class PenandaTte
{
    public function selesaikan(string $pdf): string
    {
        if (config('surat.tte.mode') !== 'bsre') {
            return $pdf;
        }

        $url = (string) config('surat.tte.url');
        $token = (string) config('surat.tte.token');

        if ($url === '' || $token === '') {
            throw new RuntimeException('TTE tersertifikasi belum dihubungkan. Surat tidak diterbitkan.');
        }

        try {
            $jawaban = Http::timeout(20)
                ->withToken($token)
                ->attach('dokumen', $pdf, 'surat.pdf')
                ->post($url, [
                    'nik' => (string) config('surat.tte.nik'),
                ]);
        } catch (Throwable) {
            throw new RuntimeException('Gateway TTE tidak dapat dihubungi. Surat tidak diterbitkan.');
        }

        $badan = $jawaban->body();

        if (! $jawaban->successful() || ! str_starts_with($badan, '%PDF')) {
            throw new RuntimeException('Gateway TTE tidak mengembalikan PDF. Surat tidak diterbitkan.');
        }

        return $badan;
    }
}
