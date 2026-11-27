<?php

namespace App\Support;

use App\Models\JenisLayanan;
use App\Models\User;

class IsianSurat
{
    public function frasaStatusKartu(mixed $nilai): string
    {
        return match ((string) $nilai) {
            'terdaftar', 'Terdaftar' => 'terdaftar',
            'tidak terdaftar', 'Tidak terdaftar' => 'tidak terdaftar',
            default => '—',
        };
    }

    public function frasaTermasukKartu(mixed $nilai): string
    {
        return match ((string) $nilai) {
            'termasuk', 'Termasuk' => 'termasuk',
            'tidak termasuk', 'Tidak termasuk' => 'tidak termasuk',
            default => '—',
        };
    }

    /**
     * @param  list<string>  $nama
     * @return list<array{nama: string, label: string, wajib: bool, tipe: string}>
     */
    public function dariPenanda(array $nama): array
    {
        $katalog = $this->katalog();
        $hasil = [];

        foreach ($nama as $kunci) {
            $hasil[] = [
                'nama' => $kunci,
                'label' => $katalog[$kunci] ?? ucwords(str_replace('_', ' ', $kunci)),
                'wajib' => true,
                'tipe' => 'text',
            ];
        }

        return $hasil;
    }

    /**
     * Baris formulir yang sama dengan isi surat. Data diri di depan, isian khusus di belakang.
     *
     * @return list<array{nama: string, label: string, wajib: bool, tipe: string, dari_profil: bool, pilihan?: list<array{nilai: string, label: string}>}>
     */
    public function baris(JenisLayanan $jenis): array
    {
        if ($jenis->pernyataan) {
            return $this->barisPernyataan($jenis);
        }

        $tetap = $this->khususTetap($jenis);
        $namaTetap = array_column($tetap, 'nama');
        $khusus = [];

        foreach ($jenis->skema_field_tambahan ?? [] as $field) {
            $nama = (string) ($field['nama'] ?? '');

            if ($nama === '' || in_array($nama, $namaTetap, true)) {
                continue;
            }

            $khusus[$nama] = [
                'nama' => $nama,
                'label' => (string) ($field['label'] ?? $nama),
                'wajib' => (bool) ($field['wajib'] ?? false),
                'tipe' => 'text',
                'dari_profil' => false,
            ];
        }

        $baris = [];

        foreach ($this->identitas($jenis) as $nama => $label) {
            if (isset($khusus[$nama])) {
                continue;
            }

            $baris[] = [
                'nama' => $nama,
                'label' => $label,
                'wajib' => true,
                'tipe' => 'text',
                'dari_profil' => true,
            ];
        }

        return array_merge($baris, $tetap, array_values($khusus));
    }

    /**
     * Isian yang tercetak pada naskah resmi dan tidak boleh diganti dari skema.
     *
     * @return list<array{nama: string, label: string, wajib: bool, tipe: string, dari_profil: bool, pilihan?: list<array{nilai: string, label: string}>}>
     */
    private function khususTetap(JenisLayanan $jenis): array
    {
        if ($jenis->kode === 'SKKES') {
            return [
                [
                    'nama' => 'tujuan',
                    'label' => 'Tujuan',
                    'wajib' => true,
                    'tipe' => 'text',
                    'dari_profil' => false,
                ],
                [
                    'nama' => 'keperluan',
                    'label' => 'Keperluan',
                    'wajib' => true,
                    'tipe' => 'text',
                    'dari_profil' => false,
                ],
                [
                    'nama' => 'status_kartu',
                    'label' => 'Status dalam Kartu Keluarga Miskin dan Kartu Keluarga Rentan Miskin',
                    'wajib' => true,
                    'tipe' => 'pilihan',
                    'dari_profil' => false,
                    'pilihan' => [
                        ['nilai' => 'termasuk', 'label' => 'Termasuk'],
                        ['nilai' => 'tidak termasuk', 'label' => 'Tidak termasuk'],
                    ],
                ],
            ];
        }

        if ($jenis->kode !== 'SKTM') {
            return [];
        }

        return [
            [
                'nama' => 'status_kartu',
                'label' => 'Status dalam Kartu Keluarga Miskin atau Kartu Keluarga Rentan Miskin',
                'wajib' => true,
                'tipe' => 'pilihan',
                'dari_profil' => false,
                'pilihan' => [
                    ['nilai' => 'terdaftar', 'label' => 'Terdaftar'],
                    ['nilai' => 'tidak terdaftar', 'label' => 'Tidak terdaftar'],
                ],
            ],
            [
                'nama' => 'keperluan',
                'label' => 'Keperluan',
                'wajib' => true,
                'tipe' => 'text',
                'dari_profil' => false,
            ],
            [
                'nama' => 'nama_anak',
                'label' => 'Nama anak',
                'wajib' => true,
                'tipe' => 'text',
                'dari_profil' => false,
            ],
            [
                'nama' => 'nik_anak',
                'label' => 'NIK anak',
                'wajib' => true,
                'tipe' => 'text',
                'dari_profil' => false,
            ],
            [
                'nama' => 'ttl_anak',
                'label' => 'Tempat, tanggal lahir anak',
                'wajib' => true,
                'tipe' => 'text',
                'dari_profil' => false,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function awal(User $user): array
    {
        $user->loadMissing('profilWarga.penduduk');
        $profil = $user->profilWarga;
        $penduduk = $profil?->penduduk;

        return [
            'nama' => (string) ($penduduk->nama ?? $user->name),
            'nik' => (string) ($user->nik ?? $penduduk?->nik ?? ''),
            'tempat_lahir' => (string) ($profil?->tempat_lahir ?? ''),
            'tanggal_lahir' => $profil?->tanggal_lahir?->translatedFormat('d F Y') ?? '',
            'jenis_kelamin' => (string) ($profil?->jenis_kelamin ?? ''),
            'warga_negara' => 'Indonesia',
            'agama' => (string) ($profil?->agama ?? ''),
            'pekerjaan' => (string) ($profil?->pekerjaan ?? ''),
            'alamat' => (string) ($profil?->alamat_lengkap ?? $penduduk?->alamat_asli ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $tersimpan
     */
    public function nilai(string $nama, array $tersimpan, array $awal): string
    {
        if (array_key_exists($nama, $tersimpan) && $tersimpan[$nama] !== null && $tersimpan[$nama] !== '') {
            return (string) $tersimpan[$nama];
        }

        return (string) ($awal[$nama] ?? '');
    }

    /**
     * @return list<array{nama: string, label: string, wajib: bool, tipe: string, dari_profil: bool}>
     */
    private function barisPernyataan(JenisLayanan $jenis): array
    {
        $katalog = $this->katalog();
        $baris = [];

        foreach ($jenis->skema_field_tambahan ?? [] as $field) {
            $nama = (string) ($field['nama'] ?? '');

            if ($nama === '') {
                continue;
            }

            $baris[] = [
                'nama' => $nama,
                'label' => (string) ($field['label'] ?? $nama),
                'wajib' => (bool) ($field['wajib'] ?? true),
                'tipe' => 'text',
                'dari_profil' => isset($katalog[$nama]),
            ];
        }

        return $baris;
    }

    /**
     * @return array<string, string>
     */
    private function katalog(): array
    {
        return [
            'nama' => 'Nama lengkap',
            'nik' => 'Nomor Induk Kependudukan (NIK)',
            'tempat_lahir' => 'Tempat lahir',
            'tanggal_lahir' => 'Tanggal lahir',
            'jenis_kelamin' => 'Jenis kelamin',
            'warga_negara' => 'Warga negara',
            'agama' => 'Agama',
            'pekerjaan' => 'Pekerjaan',
            'alamat' => 'Alamat',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function identitas(JenisLayanan $jenis): array
    {
        $katalog = $this->katalog();

        $pakai = match ($jenis->kode) {
            'SKU', 'SKKES' => array_keys($katalog),
            'DOM', 'SKCK' => ['nama', 'nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'pekerjaan', 'alamat'],
            'SKTM', 'PHSL' => ['nama', 'nik', 'tempat_lahir', 'tanggal_lahir', 'pekerjaan', 'alamat'],
            default => $this->dariNaskah($jenis, array_keys($katalog)),
        };

        return array_intersect_key($katalog, array_flip($pakai));
    }

    /**
     * @param  list<string>  $kenal
     * @return list<string>
     */
    private function dariNaskah(JenisLayanan $jenis, array $kenal): array
    {
        preg_match_all('/\{\{\s*([a-z0-9_]+)\s*\}\}/', (string) $jenis->naskah, $cocok);
        $pakai = array_values(array_intersect($kenal, $cocok[1] ?? []));

        return $pakai !== [] ? $pakai : ['nama', 'nik', 'alamat'];
    }
}
