<?php

namespace App\Services;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class KodeQr
{
    public function dataUri(string $isi): string
    {
        $hasil = (new QRCode(new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => true,
            'scale' => 4,
        ])))->render($isi);

        if (str_starts_with($hasil, 'data:image')) {
            return $hasil;
        }

        return 'data:image/png;base64,'.base64_encode($hasil);
    }
}
