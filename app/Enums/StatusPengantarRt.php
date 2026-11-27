<?php

namespace App\Enums;

enum StatusPengantarRt: string
{
    case TidakPerlu = 'tidak_perlu';
    case MenungguUnggah = 'menunggu_unggah';
    case Diunggah = 'diunggah';
    case MenungguRt = 'menunggu_rt';
    case DisetujuiRt = 'disetujui_rt';
    case DitolakRt = 'ditolak_rt';

    public function label(): string
    {
        return match ($this) {
            self::TidakPerlu => 'Tidak perlu',
            self::MenungguUnggah => 'Menunggu unggah',
            self::Diunggah => 'Foto sudah diunggah',
            self::MenungguRt => 'Menunggu RT/RW',
            self::DisetujuiRt => 'Disetujui RT/RW',
            self::DitolakRt => 'Ditolak RT/RW',
        };
    }
}
