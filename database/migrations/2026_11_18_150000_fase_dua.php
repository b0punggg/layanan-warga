<?php

use App\Support\KatalogLayanan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dukuh')->nullable()->after('nik');
            $table->string('rt', 5)->nullable()->after('dukuh');
            $table->string('rw', 5)->nullable()->after('rt');
        });

        Schema::table('notifikasi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('notifikasi', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('tujuan', 100)->nullable()->after('user_id');
        });

        KatalogLayanan::faseDua();

        if (DB::table('users')->where('email', 'admin@kalurahan.local')->exists()
            && ! DB::table('users')->where('email', 'rt@kalurahan.local')->exists()) {
            DB::table('users')->insert([
                'name' => 'Ketua RT 001 RW 002',
                'email' => 'rt@kalurahan.local',
                'peran' => 'rt_rw',
                'status_akun' => 'aktif',
                'dukuh' => 'Contoh',
                'rt' => '001',
                'rw' => '002',
                'password' => Hash::make((string) config('surat.kata_sandi_percobaan')),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('profil_warga')->where('email', 'warga@kalurahan.local')->update([
            'dukuh' => 'Contoh',
            'rt' => '001',
            'rw' => '002',
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dukuh', 'rt', 'rw']);
        });

        DB::table('jenis_layanan')->whereIn('kode', ['PHSL', 'SKCK'])->delete();
    }
};
