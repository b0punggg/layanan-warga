<?php

namespace App\Support;

use App\Enums\ModePengantar;
use App\Models\JenisLayanan;

class KatalogLayanan
{
    public static function faseDua(): void
    {
        $syarat = [
            ['nama' => 'ktp', 'label' => 'Foto KTP', 'wajib' => true],
            ['nama' => 'kk', 'label' => 'Foto Kartu Keluarga', 'wajib' => false],
        ];

        JenisLayanan::query()->updateOrCreate(['kode' => 'PHSL'], [
            'nama' => 'Surat Keterangan Penghasilan',
            'deskripsi' => 'Keterangan perkiraan penghasilan. Draf, pengantar RT/RW masih usulan kalurahan.',
            'butuh_pengantar_rt' => true,
            'mode_pengantar' => ModePengantar::VerifikasiOnline,
            'daftar_syarat' => $syarat,
            'skema_field_tambahan' => [
                ['nama' => 'jumlah_penghasilan', 'label' => 'Perkiraan penghasilan per bulan', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
            ],
            'template_surat' => 'surat.templates.penghasilan',
            'estimasi_hari' => 3,
            'aktif' => true,
        ]);

        JenisLayanan::query()->updateOrCreate(['kode' => 'SKCK'], [
            'nama' => 'Pengantar SKCK',
            'deskripsi' => 'Pengantar untuk pengurusan SKCK. Draf, belum dikonfirmasi kalurahan.',
            'butuh_pengantar_rt' => false,
            'mode_pengantar' => ModePengantar::Unggah,
            'daftar_syarat' => $syarat,
            'skema_field_tambahan' => [
                ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
            ],
            'template_surat' => 'surat.templates.skck',
            'estimasi_hari' => 2,
            'aktif' => true,
        ]);
    }

    public static function faseTiga(): void
    {
        $syarat = [
            ['nama' => 'ktp', 'label' => 'Foto KTP', 'wajib' => true],
            ['nama' => 'kk', 'label' => 'Foto Kartu Keluarga', 'wajib' => false],
        ];

        $jenis = [
            [
                'kode' => 'NIKAH',
                'nama' => 'Surat Pengantar Nikah',
                'deskripsi' => 'Draf keterangan untuk pengantar nikah. Format resmi belum dikunci kalurahan.',
                'field' => [
                    ['nama' => 'status_perkawinan', 'label' => 'Status perkawinan', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'nama_pasangan', 'label' => 'Nama pasangan', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
                ],
                'naskah' => "SURAT PENGANTAR NIKAH\n\nNomor: {{nomor}}\n\nYang bertanda tangan di bawah ini menerangkan bahwa {{nama}}, NIK {{nik}}, lahir di {{tempat_lahir}} pada {{tanggal_lahir}}, pekerjaan {{pekerjaan}}, beralamat di {{alamat}}.\n\nStatus perkawinan: {{status_perkawinan}}. Nama pasangan: {{nama_pasangan}}.\n\nSurat ini dibuat untuk keperluan {{keperluan}}.\n\nNaskah ini draf dan belum merupakan format resmi kalurahan.",
            ],
            [
                'kode' => 'LAHIR',
                'nama' => 'Surat Keterangan Kelahiran',
                'deskripsi' => 'Draf keterangan kelahiran. Format resmi belum dikunci kalurahan.',
                'field' => [
                    ['nama' => 'nama_anak', 'label' => 'Nama anak', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'hari_lahir', 'label' => 'Hari dan tanggal lahir', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'nama_ayah', 'label' => 'Nama ayah', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'nama_ibu', 'label' => 'Nama ibu', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
                ],
                'naskah' => "SURAT KETERANGAN KELAHIRAN\n\nNomor: {{nomor}}\n\nMenerangkan kelahiran {{nama_anak}} pada {{hari_lahir}}. Ayah: {{nama_ayah}}. Ibu: {{nama_ibu}}.\n\nPemohon: {{nama}}, NIK {{nik}}, alamat {{alamat}}.\n\nKeperluan: {{keperluan}}.\n\nNaskah ini draf dan belum merupakan format resmi kalurahan.",
            ],
            [
                'kode' => 'MATI',
                'nama' => 'Surat Keterangan Kematian',
                'deskripsi' => 'Draf keterangan kematian. Format resmi belum dikunci kalurahan.',
                'field' => [
                    ['nama' => 'nama_almarhum', 'label' => 'Nama almarhum atau almarhumah', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'tanggal_meninggal', 'label' => 'Tanggal meninggal', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'hubungan', 'label' => 'Hubungan dengan pemohon', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
                ],
                'naskah' => "SURAT KETERANGAN KEMATIAN\n\nNomor: {{nomor}}\n\nMenerangkan bahwa {{nama_almarhum}} meninggal pada {{tanggal_meninggal}}.\n\nPemohon {{nama}}, NIK {{nik}}, hubungan: {{hubungan}}, alamat {{alamat}}.\n\nKeperluan: {{keperluan}}.\n\nNaskah ini draf dan belum merupakan format resmi kalurahan.",
            ],
            [
                'kode' => 'PINDAH',
                'nama' => 'Surat Pengantar Pindah atau Datang',
                'deskripsi' => 'Draf pengantar pindah atau datang. Format resmi belum dikunci kalurahan.',
                'field' => [
                    ['nama' => 'jenis_perpindahan', 'label' => 'Pindah atau datang', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'alamat_tujuan', 'label' => 'Alamat tujuan atau asal', 'tipe' => 'text', 'wajib' => true],
                    ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'text', 'wajib' => true],
                ],
                'naskah' => "SURAT PENGANTAR PINDAH ATAU DATANG\n\nNomor: {{nomor}}\n\nMenerangkan bahwa {{nama}}, NIK {{nik}}, alamat {{alamat}}, mengajukan perpindahan: {{jenis_perpindahan}}.\n\nAlamat terkait: {{alamat_tujuan}}.\n\nKeperluan: {{keperluan}}.\n\nNaskah ini draf dan belum merupakan format resmi kalurahan.",
            ],
        ];

        foreach ($jenis as $item) {
            JenisLayanan::query()->updateOrCreate(['kode' => $item['kode']], [
                'nama' => $item['nama'],
                'deskripsi' => $item['deskripsi'],
                'butuh_pengantar_rt' => false,
                'mode_pengantar' => ModePengantar::Unggah,
                'daftar_syarat' => $syarat,
                'skema_field_tambahan' => $item['field'],
                'template_surat' => 'surat.templates.dinamis',
                'naskah' => $item['naskah'],
                'estimasi_hari' => 3,
                'aktif' => true,
            ]);
        }
    }
}
