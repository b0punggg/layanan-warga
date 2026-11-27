<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penghitung_nomor', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun')->primary();
            $table->unsignedInteger('terakhir')->default(0);
        });

        Schema::create('otp_pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->char('nik', 16);
            $table->string('nama');
            $table->string('no_hp', 20);
            $table->boolean('cocok_penduduk')->default(false);
            $table->string('kode_hash');
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->timestamp('kedaluwarsa_pada');
            $table->timestamp('terverifikasi_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_pendaftaran');
        Schema::dropIfExists('penghitung_nomor');
    }
};
