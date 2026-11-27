<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_layanan', function (Blueprint $table) {
            $table->boolean('pernyataan')->default(false)->after('aktif');
            $table->string('berkas_template')->nullable()->after('pernyataan');
        });

        Schema::table('pengajuan', function (Blueprint $table) {
            $table->string('file_hasil')->nullable()->after('berkas_pengantar_path');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan', function (Blueprint $table) {
            $table->dropColumn('file_hasil');
        });

        Schema::table('jenis_layanan', function (Blueprint $table) {
            $table->dropColumn(['pernyataan', 'berkas_template']);
        });
    }
};
