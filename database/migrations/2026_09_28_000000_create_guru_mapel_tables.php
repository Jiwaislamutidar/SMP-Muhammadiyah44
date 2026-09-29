<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gurus', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->nullable()->unique();
            $table->string('nama_lengkap')->unique();
            $table->string('status')->default('aktif');
            $table->timestamps();
        });

        Schema::create('mapels', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama_mapel');
            $table->timestamps();
        });

        Schema::create('guru_mapel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
            $table->unsignedSmallInteger('jumlah_jam')->default(0);
            $table->timestamps();
            $table->unique(['guru_id', 'mapel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_mapel');
        Schema::dropIfExists('mapels');
        Schema::dropIfExists('gurus');
    }
};
