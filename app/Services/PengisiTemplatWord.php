<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class PengisiTemplatWord
{
    public function __construct(private BerkasPrivat $berkas) {}

    /**
     * @param  array<string, scalar|null>  $data
     */
    public function isi(string $path, array $data): string
    {
        return $this->ganti($this->berkas->baca($path), $data);
    }

    /**
     * @return list<string>
     */
    public function penanda(string $docx): array
    {
        $xml = $this->xml($docx);
        preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}|\$\{\s*([A-Za-z0-9_]+)\s*\}/', $xml, $cocok, PREG_SET_ORDER);
        $nama = [];

        foreach ($cocok as $item) {
            $kunci = strtolower($item[1] !== '' ? $item[1] : $item[2]);
            $nama[$kunci] = $kunci;
        }

        if ($nama === []) {
            $polos = strip_tags($xml);

            if (preg_match('/\{\{\s*[A-Za-z0-9_]+\s*\}\}|\$\{\s*[A-Za-z0-9_]+\s*\}/', $polos) === 1) {
                throw new RuntimeException('Penanda isian terpecah oleh Word. Ketik ulang {{nama}} sekaligus, tanpa mengubah huruf di tengahnya.');
            }
        }

        return array_values($nama);
    }

    /**
     * @param  array<string, scalar|null>  $data
     */
    private function ganti(string $docx, array $data): string
    {
        return $this->ubahXml($docx, function (string $xml) use ($data): string {
            return preg_replace_callback(
                '/\{\{\s*([A-Za-z0-9_]+)\s*\}\}|\$\{\s*([A-Za-z0-9_]+)\s*\}/',
                function (array $cocok) use ($data): string {
                    $kunci = strtolower($cocok[1] !== '' ? $cocok[1] : $cocok[2]);

                    return htmlspecialchars((string) ($data[$kunci] ?? '—'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                },
                $xml,
            ) ?? $xml;
        });
    }

    private function xml(string $docx): string
    {
        $hasil = '';
        $this->ubahXml($docx, function (string $xml) use (&$hasil): string {
            $hasil = $xml;

            return $xml;
        });

        return $hasil;
    }

    /**
     * @param  callable(string): string  $ubah
     */
    private function ubahXml(string $docx, callable $ubah): string
    {
        $sementara = tempnam(sys_get_temp_dir(), 'docx');

        if ($sementara === false) {
            throw new RuntimeException('Berkas template tidak dapat disiapkan.');
        }

        file_put_contents($sementara, $docx);
        $zip = new ZipArchive;
        $terbuka = $zip->open($sementara);

        if ($terbuka !== true) {
            @unlink($sementara);

            throw new RuntimeException('Berkas template tidak dapat dibaca. Unggah file Word (.docx).');
        }

        $xml = $zip->getFromName('word/document.xml');

        if (! is_string($xml)) {
            $zip->close();
            @unlink($sementara);

            throw new RuntimeException('Isi template tidak ditemukan.');
        }

        $zip->addFromString('word/document.xml', $ubah($xml));
        $zip->close();
        $hasil = (string) file_get_contents($sementara);
        @unlink($sementara);

        return $hasil;
    }
}
