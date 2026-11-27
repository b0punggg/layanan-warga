<?php

namespace App\Models;

use App\Enums\ModePengantar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nama',
    'kode',
    'deskripsi',
    'butuh_pengantar_rt',
    'mode_pengantar',
    'daftar_syarat',
    'skema_field_tambahan',
    'template_surat',
    'naskah',
    'format_nomor',
    'estimasi_hari',
    'aktif',
    'pernyataan',
    'berkas_template',
])]
class JenisLayanan extends Model
{
    protected $table = 'jenis_layanan';

    protected function casts(): array
    {
        return [
            'butuh_pengantar_rt' => 'boolean',
            'aktif' => 'boolean',
            'pernyataan' => 'boolean',
            'daftar_syarat' => 'array',
            'skema_field_tambahan' => 'array',
            'mode_pengantar' => ModePengantar::class,
        ];
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }

    public function tampilanSurat(): string
    {
        return filled($this->naskah) ? 'surat.templates.dinamis' : $this->template_surat;
    }

    public function isianTerkunci(): bool
    {
        return $this->pernyataan || in_array($this->kode, ['SKU', 'DOM', 'SKCK', 'SKTM', 'SKKES', 'PHSL'], true);
    }

    public function naskahResmi(): bool
    {
        return in_array($this->kode, ['SKU', 'SKTM', 'SKKES'], true);
    }
}
