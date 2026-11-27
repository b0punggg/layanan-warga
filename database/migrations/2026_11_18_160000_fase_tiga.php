<?php

use App\Support\KatalogLayanan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_layanan', function (Blueprint $table) {
            $table->longText('naskah')->nullable()->after('template_surat');
        });

        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nama');
            $table->string('no_hp', 20);
            $table->string('email')->nullable();
            $table->string('judul');
            $table->text('isi');
            $table->string('status', 20)->default('baru')->index();
            $table->text('tanggapan')->nullable();
            $table->foreignId('ditanggapi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditanggapi_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('informasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('judul');
            $table->string('ringkasan');
            $table->text('isi');
            $table->boolean('terbit')->default(false)->index();
            $table->timestamp('diterbitkan_pada')->nullable();
            $table->timestamps();
        });

        KatalogLayanan::faseTiga();

        if (! DB::table('informasi')->exists()) {
            DB::table('informasi')->insert([
                'judul' => 'Contoh informasi publik',
                'ringkasan' => 'Teks ini contoh. Ganti dengan pengumuman resmi kalurahan.',
                'isi' => 'Jam layanan, biaya, dan kanal aduan resmi diisi oleh kalurahan. Jangan anggap teks ini sebagai pengumuman yang berlaku.',
                'terbit' => true,
                'diterbitkan_pada' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('informasi');
        Schema::dropIfExists('pengaduan');
        Schema::table('jenis_layanan', function (Blueprint $table) {
            $table->dropColumn('naskah');
        });
    }
};
