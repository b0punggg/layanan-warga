<?php

namespace App\Services;

class PenyusunNaskah
{
    /**
     * @param  array<string, scalar|null>  $data
     */
    public function html(?string $naskah, array $data): string
    {
        if (! filled($naskah)) {
            return '';
        }

        $aman = e($naskah);
        $aman = preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function (array $cocok) use ($data): string {
            $kunci = strtolower($cocok[1]);

            return e((string) ($data[$kunci] ?? '—'));
        }, $aman) ?? $aman;

        return nl2br($aman, false);
    }
}
