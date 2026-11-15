<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table): void {
            $table->string('tingkat', 10)->nullable();
            $table->string('wali_kelas')->nullable();
            $table->string('ruangan', 100)->nullable();
            $table->unsignedInteger('jumlah_murid')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table): void {
            $table->dropColumn(['tingkat', 'wali_kelas', 'ruangan', 'jumlah_murid']);
        });
    }
};
