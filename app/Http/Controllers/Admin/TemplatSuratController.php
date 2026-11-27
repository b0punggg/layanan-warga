<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisLayanan;
use App\Services\NomorSurat;
use App\Services\PenyusunNaskah;
use Illuminate\View\View;

class TemplatSuratController extends Controller
{
    public function show(string $kode, NomorSurat $nomorSurat, PenyusunNaskah $naskah): View
    {
        $jenis = JenisLayanan::query()->where('kode', $kode)->firstOrFail();

        abort_unless(view()->exists($jenis->tampilanSurat()), 404);

        $nomor = $nomorSurat->susun($jenis->kode, 1, now(), $jenis->format_nomor);
        $contoh = [
            'nama' => 'Sulastri',
            'nik' => '3400000000000001',
            'tempat_lahir' => 'Sleman',
            'tanggal_lahir' => '1 Januari 1990',
            'jenis_kelamin' => 'Perempuan',
            'warga_negara' => 'Indonesia',
            'agama' => 'Islam',
            'pekerjaan' => 'Wiraswasta',
            'alamat' => 'Dukuh Contoh, RT 001/RW 002',
            'nama_usaha' => 'Warung Contoh',
            'jenis_usaha' => 'Perdagangan',
            'alamat_usaha' => 'Dukuh Contoh',
            'keperluan' => 'Kelengkapan administrasi',
            'status_kartu' => 'terdaftar',
            'status_kartu_kesehatan' => 'termasuk',
            'tujuan' => 'Berobat',
            'nik_anak' => '3400000000000002',
            'ttl_anak' => 'Sleman, 2 Februari 2015',
            'jumlah_penghasilan' => 'Rp 1.500.000',
            'status_perkawinan' => 'Belum kawin',
            'nama_pasangan' => '—',
            'nama_anak' => 'Budi Santoso',
            'hari_lahir' => '—',
            'nama_ayah' => '—',
            'nama_ibu' => '—',
            'nama_almarhum' => '—',
            'tanggal_meninggal' => '—',
            'hubungan' => '—',
            'jenis_perpindahan' => '—',
            'alamat_tujuan' => '—',
        ];

        return view('admin.templat.pratinjau', [
            'jenis' => $jenis,
            'nomor' => $nomor,
            'contoh' => $contoh,
            'badan' => $naskah->html($jenis->naskah, array_merge($contoh, ['nomor' => $nomor])),
        ]);
    }
}
