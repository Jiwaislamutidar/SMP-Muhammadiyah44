<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained('users')->nullOnDelete();
        });

        Schema::create('jadwal_pelajarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
            $table->string('hari', 20);
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('ruangan')->nullable();
            $table->timestamps();
            $table->index(['hari', 'guru_id']);
            $table->index(['hari', 'kelas_id']);
        });

        Schema::create('presensi_gurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->string('foto_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->string('foto_pulang')->nullable();
            $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Alfa'])->default('Hadir');
            $table->timestamps();
            $table->unique(['guru_id', 'tanggal']);
        });

        Schema::create('sesi_pelajarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal_pelajarans')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('qr_token')->nullable()->unique();
            $table->timestamp('qr_expires_at')->nullable();
            $table->enum('status_sesi', ['Belum', 'Berlangsung', 'Selesai'])->default('Belum');
            $table->timestamps();
            $table->unique(['jadwal_id', 'tanggal']);
        });

        Schema::create('presensi_pelajarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_pelajaran_id')->constrained('sesi_pelajarans')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->timestamp('waktu_scan')->nullable();
            $table->enum('status', ['Belum Absen', 'Hadir', 'Izin', 'Sakit', 'Alfa'])->default('Belum Absen');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['sesi_pelajaran_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_pelajarans');
        Schema::dropIfExists('sesi_pelajarans');
        Schema::dropIfExists('presensi_gurus');
        Schema::dropIfExists('jadwal_pelajarans');

        Schema::table('gurus', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};