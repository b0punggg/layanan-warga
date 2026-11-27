<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_impor', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('waktu');
            $table->unsignedInteger('baris_total')->default(0);
            $table->unsignedInteger('baris_sukses')->default(0);
            $table->unsignedInteger('baris_gagal')->default(0);
            $table->string('laporan_gagal_path')->nullable();
        });

        Schema::create('penduduk', function (Blueprint $table) {
            $table->id();
            $table->char('nik', 16)->unique();
            $table->string('nama');
            $table->text('alamat_asli');
            $table->string('dukuh')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('status_data', 20)->default('menunggu');
            $table->string('sumber_data')->nullable();
            $table->date('tanggal_impor')->nullable();
            $table->foreignId('riwayat_impor_id')->nullable()->constrained('riwayat_impor')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('profil_warga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penduduk_id')->unique()->constrained('penduduk');
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->char('no_kk', 16)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 20)->nullable();
            $table->string('agama', 30)->nullable();
            $table->string('status_kawin', 30)->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('dukuh')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->text('alamat_lengkap')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('foto_ktp_path')->nullable();
            $table->string('foto_selfie_path')->nullable();
            $table->string('status_verifikasi', 20)->default('menunggu');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('jenis_layanan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode', 20)->unique();
            $table->text('deskripsi')->nullable();
            $table->boolean('butuh_pengantar_rt')->default(false);
            $table->string('mode_pengantar', 30)->default('unggah');
            $table->json('daftar_syarat');
            $table->json('skema_field_tambahan');
            $table->string('template_surat');
            $table->string('format_nomor')->nullable();
            $table->unsignedTinyInteger('estimasi_hari')->default(3);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('pengajuan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tiket')->unique();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('jenis_layanan_id')->constrained('jenis_layanan');
            $table->json('data_form');
            $table->string('status', 30)->default('draft')->index();
            $table->string('status_pengantar_rt', 30)->nullable();
            $table->string('berkas_pengantar_path')->nullable();
            $table->timestamp('tanggal_ajuan')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->timestamps();
        });

        Schema::create('berkas_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan')->cascadeOnDelete();
            $table->string('nama_syarat');
            $table->string('path_file');
            $table->string('tipe', 100);
            $table->unsignedInteger('ukuran');
            $table->timestamp('diunggah_pada');
        });

        Schema::create('pejabat_kalurahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nama');
            $table->string('jabatan');
            $table->string('file_ttd')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->unique()->constrained('pengajuan');
            $table->string('nomor_surat')->unique();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('nomor_urut');
            $table->string('file_pdf')->nullable();
            $table->date('tanggal_terbit')->nullable();
            $table->foreignId('penandatangan_id')->nullable()->constrained('pejabat_kalurahan')->nullOnDelete();
            $table->string('kode_verifikasi', 64)->nullable()->unique();
            $table->timestamp('tanggal_diambil')->nullable();
            $table->timestamps();

            $table->unique(['tahun', 'nomor_urut']);
        });

        Schema::create('riwayat_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan')->cascadeOnDelete();
            $table->string('status_lama', 30)->nullable();
            $table->string('status_baru', 30);
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('waktu');
        });

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('kanal', 20);
            $table->text('isi');
            $table->string('status_kirim', 20)->default('antre');
            $table->timestamp('waktu');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('aksi', 50);
            $table->string('objek')->nullable();
            $table->json('detail')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('riwayat_status');
        Schema::dropIfExists('surat');
        Schema::dropIfExists('pejabat_kalurahan');
        Schema::dropIfExists('berkas_pengajuan');
        Schema::dropIfExists('pengajuan');
        Schema::dropIfExists('jenis_layanan');
        Schema::dropIfExists('profil_warga');
        Schema::dropIfExists('penduduk');
        Schema::dropIfExists('riwayat_impor');
    }
};
