<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\BerandaController as AdminBerandaController;
use App\Http\Controllers\Admin\ImporController;
use App\Http\Controllers\Admin\InformasiController;
use App\Http\Controllers\Admin\LayananController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\PersiapanController;
use App\Http\Controllers\Admin\TemplatSuratController;
use App\Http\Controllers\ArsipController;
use App\Http\Controllers\Auth\DaftarController;
use App\Http\Controllers\Auth\MasukController;
use App\Http\Controllers\Auth\SandiController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\BerkasController;
use App\Http\Controllers\CekSuratController;
use App\Http\Controllers\InformasiPublikController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\Lurah\BerandaController as LurahBerandaController;
use App\Http\Controllers\Lurah\PengajuanController as LurahPengajuanController;
use App\Http\Controllers\Operator\AkunController;
use App\Http\Controllers\Operator\BerandaController as OperatorBerandaController;
use App\Http\Controllers\Operator\PengajuanController as OperatorPengajuanController;
use App\Http\Controllers\Operator\TemplatController as OperatorTemplatController;
use App\Http\Controllers\PendudukController;
use App\Http\Controllers\PengaduanController;
use App\Http\Controllers\Rt\PengajuanController as RtPengajuanController;
use App\Http\Controllers\Warga\BerandaController as WargaBerandaController;
use App\Http\Controllers\Warga\PengajuanController as WargaPengajuanController;
use App\Http\Controllers\Warga\PesanController;
use App\Http\Controllers\Warga\ProfilController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'halaman.awal')->name('awal');
Route::get('/informasi', [InformasiPublikController::class, 'index'])->name('informasi.index');
Route::get('/informasi/{informasi}', [InformasiPublikController::class, 'lihat'])->whereNumber('informasi')->name('informasi.lihat');
Route::get('/pengaduan', [PengaduanController::class, 'buat'])->name('pengaduan.buat');
Route::post('/pengaduan', [PengaduanController::class, 'simpan'])->name('pengaduan.simpan');
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/cek', [CekSuratController::class, 'form'])->name('cek.form');
    Route::get('/cek/{kode}', [CekSuratController::class, 'lihat'])->where('kode', '[A-Za-z0-9]{16,64}')->name('cek.surat');
});

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [MasukController::class, 'create'])->name('masuk');
    Route::post('/masuk', [MasukController::class, 'store'])->name('masuk.store');
    Route::get('/daftar', [DaftarController::class, 'buat'])->name('daftar');
    Route::post('/daftar', [DaftarController::class, 'mulai'])->name('daftar.mulai');
    Route::get('/daftar/otp', [DaftarController::class, 'otp'])->name('daftar.otp');
    Route::post('/daftar/otp', [DaftarController::class, 'periksaOtp'])->name('daftar.otp.periksa');
    Route::get('/daftar/profil', [DaftarController::class, 'profil'])->name('daftar.profil');
    Route::post('/daftar/profil/baca', [DaftarController::class, 'baca'])->middleware('throttle:12,1')->name('daftar.profil.baca');
    Route::post('/daftar/profil', [DaftarController::class, 'simpan'])->name('daftar.profil.simpan');
    Route::get('/sandi/lupa', [SandiController::class, 'minta'])->name('sandi.minta');
    Route::post('/sandi/lupa', [SandiController::class, 'kirim'])->name('sandi.kirim');
    Route::get('/sandi/atur/{token}', [SandiController::class, 'atur'])->name('password.reset');
    Route::post('/sandi/atur', [SandiController::class, 'simpan'])->name('password.update');
});

Route::post('/keluar', [MasukController::class, 'destroy'])->middleware('auth')->name('keluar');
Route::get('/beranda', BerandaController::class)->middleware('auth')->name('beranda');

Route::middleware(['auth', 'peran:warga'])->prefix('warga')->name('warga.')->group(function () {
    Route::get('/', WargaBerandaController::class)->name('beranda');
    Route::get('/menunggu', [ProfilController::class, 'lihat'])->name('menunggu');
    Route::get('/profil', [ProfilController::class, 'lihat'])->name('profil');
    Route::put('/profil', [ProfilController::class, 'simpan'])->name('profil.simpan');
    Route::get('/pesan', [PesanController::class, 'index'])->name('pesan');
    Route::get('/pengaduan', [PengaduanController::class, 'milik'])->name('pengaduan');
    Route::get('/pengajuan', [WargaPengajuanController::class, 'index'])->name('pengajuan.index');
    Route::get('/pengajuan/baru', [WargaPengajuanController::class, 'layanan'])->name('pengajuan.layanan');
    Route::get('/pengajuan/baru/{jenisLayanan}', [WargaPengajuanController::class, 'buat'])->name('pengajuan.buat');
    Route::post('/pengajuan/baru/{jenisLayanan}', [WargaPengajuanController::class, 'simpan'])->name('pengajuan.simpan');
    Route::get('/pengajuan/{pengajuan}', [WargaPengajuanController::class, 'lihat'])->name('pengajuan.lihat');
    Route::put('/pengajuan/{pengajuan}', [WargaPengajuanController::class, 'perbarui'])->name('pengajuan.perbarui');
});

Route::middleware(['auth', 'peran:operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/', OperatorBerandaController::class)->name('beranda');
    Route::get('/templat', [OperatorTemplatController::class, 'index'])->name('templat.index');
    Route::get('/templat/{jenisLayanan}', [OperatorTemplatController::class, 'lihat'])->name('templat.lihat');
    Route::put('/templat/{jenisLayanan}', [OperatorTemplatController::class, 'simpan'])->name('templat.simpan');
    Route::get('/templat/{jenisLayanan}/word', [OperatorTemplatController::class, 'word'])->name('templat.word');
    Route::get('/pengajuan', [OperatorPengajuanController::class, 'index'])->name('pengajuan.index');
    Route::get('/pengajuan/{pengajuan}', [OperatorPengajuanController::class, 'lihat'])->name('pengajuan.lihat');
    Route::put('/pengajuan/{pengajuan}', [OperatorPengajuanController::class, 'sunting'])->name('pengajuan.sunting');
    Route::post('/pengajuan/{pengajuan}/perbaikan', [OperatorPengajuanController::class, 'perbaikan'])->name('pengajuan.perbaikan');
    Route::post('/pengajuan/{pengajuan}/tolak', [OperatorPengajuanController::class, 'tolak'])->name('pengajuan.tolak');
    Route::post('/pengajuan/{pengajuan}/kirim', [OperatorPengajuanController::class, 'kirim'])->name('pengajuan.kirim');
    Route::get('/pengajuan/{pengajuan}/naskah', [OperatorPengajuanController::class, 'naskah'])->name('pengajuan.naskah');
    Route::get('/pengajuan/{pengajuan}/naskah-word', [OperatorPengajuanController::class, 'naskahWord'])->name('pengajuan.naskah.word');
    Route::post('/pengajuan/{pengajuan}/terbit', [OperatorPengajuanController::class, 'terbit'])->name('pengajuan.terbit');
    Route::get('/pengajuan/{pengajuan}/format', [OperatorPengajuanController::class, 'format'])->name('pengajuan.format');
    Route::post('/pengajuan/{pengajuan}/format', [OperatorPengajuanController::class, 'kirimFormat'])->name('pengajuan.format.kirim');
    Route::get('/akun', [AkunController::class, 'index'])->name('akun.index');
    Route::get('/akun/baru', [AkunController::class, 'buat'])->name('akun.buat');
    Route::post('/akun', [AkunController::class, 'simpan'])->name('akun.simpan');
    Route::get('/akun/{profil}', [AkunController::class, 'lihat'])->name('akun.lihat');
    Route::post('/akun/{profil}/setujui', [AkunController::class, 'setujui'])->name('akun.setujui');
    Route::post('/akun/{profil}/perbaikan', [AkunController::class, 'perbaikan'])->name('akun.perbaikan');
    Route::post('/akun/{profil}/tolak', [AkunController::class, 'tolak'])->name('akun.tolak');
});

Route::middleware(['auth', 'peran:lurah'])->prefix('lurah')->name('lurah.')->group(function () {
    Route::get('/', LurahBerandaController::class)->name('beranda');
    Route::get('/pengajuan', [LurahPengajuanController::class, 'index'])->name('pengajuan.index');
    Route::post('/pengajuan/massal', [LurahPengajuanController::class, 'massal'])->name('pengajuan.massal');
    Route::get('/pengajuan/{pengajuan}', [LurahPengajuanController::class, 'lihat'])->name('pengajuan.lihat');
    Route::post('/pengajuan/{pengajuan}/setujui', [LurahPengajuanController::class, 'setujui'])->name('pengajuan.setujui');
    Route::post('/pengajuan/{pengajuan}/tolak', [LurahPengajuanController::class, 'tolak'])->name('pengajuan.tolak');
});

Route::middleware(['auth', 'peran:rt_rw'])->prefix('rt')->name('rt.')->group(function () {
    Route::get('/', [RtPengajuanController::class, 'index'])->name('beranda');
    Route::get('/pengajuan/{pengajuan}', [RtPengajuanController::class, 'lihat'])->name('pengajuan.lihat');
    Route::post('/pengajuan/{pengajuan}/setujui', [RtPengajuanController::class, 'setujui'])->name('pengajuan.setujui');
    Route::post('/pengajuan/{pengajuan}/tolak', [RtPengajuanController::class, 'tolak'])->name('pengajuan.tolak');
});

Route::middleware(['auth', 'peran:admin,operator'])->group(function () {
    Route::get('/pengaduan/masuk', [PengaduanController::class, 'index'])->name('pengaduan.index');
    Route::get('/pengaduan/{pengaduan}', [PengaduanController::class, 'lihat'])->whereNumber('pengaduan')->name('pengaduan.lihat');
    Route::post('/pengaduan/{pengaduan}/tanggapan', [PengaduanController::class, 'tanggapan'])->whereNumber('pengaduan')->name('pengaduan.tanggapan');
    Route::get('/penduduk', [PendudukController::class, 'index'])->name('penduduk.index');
    Route::get('/penduduk/{penduduk}/ubah', [PendudukController::class, 'ubah'])->name('penduduk.ubah');
    Route::put('/penduduk/{penduduk}', [PendudukController::class, 'simpan'])->name('penduduk.simpan');
});

Route::middleware(['auth', 'peran:admin,operator,lurah'])->group(function () {
    Route::get('/arsip', [ArsipController::class, 'index'])->name('arsip.index');
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/excel', [LaporanController::class, 'excel'])->name('laporan.excel');
    Route::get('/laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');
});

Route::middleware(['auth', 'peran:admin,operator,lurah,warga'])->group(function () {
    Route::get('/berkas/syarat/{berkas}', [BerkasController::class, 'syarat'])->name('berkas.syarat');
    Route::get('/berkas/pengantar/{pengajuan}', [BerkasController::class, 'pengantar'])->name('berkas.pengantar');
    Route::get('/berkas/identitas/{profil}/{jenis}', [BerkasController::class, 'identitas'])->name('berkas.identitas');
    Route::get('/berkas/surat/{surat}', [BerkasController::class, 'surat'])->name('berkas.surat');
    Route::get('/berkas/format/{pengajuan}', [BerkasController::class, 'format'])->name('berkas.format');
});

Route::middleware(['auth', 'peran:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminBerandaController::class)->name('beranda');
    Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
    Route::get('/pengguna/baru', [PenggunaController::class, 'create'])->name('pengguna.create');
    Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
    Route::patch('/pengguna/{user}/status', [PenggunaController::class, 'ubahStatus'])->name('pengguna.status');
    Route::get('/layanan', [LayananController::class, 'index'])->name('layanan.index');
    Route::get('/layanan/baru', [LayananController::class, 'buat'])->name('layanan.buat');
    Route::post('/layanan', [LayananController::class, 'simpan'])->name('layanan.simpan');
    Route::get('/layanan/{jenisLayanan}/ubah', [LayananController::class, 'ubah'])->name('layanan.ubah');
    Route::put('/layanan/{jenisLayanan}', [LayananController::class, 'perbarui'])->name('layanan.perbarui');
    Route::patch('/layanan/{jenisLayanan}/status', [LayananController::class, 'status'])->name('layanan.status');
    Route::get('/informasi', [InformasiController::class, 'index'])->name('informasi.index');
    Route::get('/informasi/baru', [InformasiController::class, 'buat'])->name('informasi.buat');
    Route::post('/informasi', [InformasiController::class, 'simpan'])->name('informasi.simpan');
    Route::get('/informasi/{informasi}/ubah', [InformasiController::class, 'ubah'])->name('informasi.ubah');
    Route::put('/informasi/{informasi}', [InformasiController::class, 'perbarui'])->name('informasi.perbarui');
    Route::get('/templat/{kode}', [TemplatSuratController::class, 'show'])->name('templat');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('/persiapan', PersiapanController::class)->name('persiapan');
    Route::get('/impor', [ImporController::class, 'buat'])->name('impor.buat');
    Route::post('/impor', [ImporController::class, 'pratinjau'])->name('impor.pratinjau');
    Route::post('/impor/simpan', [ImporController::class, 'simpan'])->name('impor.simpan');
    Route::get('/impor/gagal/{token}', [ImporController::class, 'gagal'])->name('impor.gagal');
    Route::get('/impor/laporan/{riwayat}', [ImporController::class, 'laporan'])->name('impor.laporan');
});
