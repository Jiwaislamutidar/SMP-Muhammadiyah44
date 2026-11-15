<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_presensi_guru_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_presensi_id')->nullable()->unique();
            $table->unsignedBigInteger('guru_id');
            $table->string('guru_nama');
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->string('foto_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->string('foto_pulang')->nullable();
            $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Alfa'])->default('Hadir');
            $table->string('bukti_izin')->nullable();
            $table->timestamps();
            $table->index(['tanggal', 'status']);
        });

        DB::table('dashboard_presensi_guru_logs')->insertUsing(
            [
                'source_presensi_id',
                'guru_id',
                'guru_nama',
                'tanggal',
                'jam_masuk',
                'foto_masuk',
                'jam_pulang',
                'foto_pulang',
                'status',
                'bukti_izin',
                'created_at',
                'updated_at',
            ],
            DB::table('presensi_gurus')
                ->leftJoin('gurus', 'gurus.id', '=', 'presensi_gurus.guru_id')
                ->select([
                    'presensi_gurus.id',
                    'presensi_gurus.guru_id',
                    DB::raw("COALESCE(gurus.nama_lengkap, 'Guru')"),
                    'presensi_gurus.tanggal',
                    'presensi_gurus.jam_masuk',
                    'presensi_gurus.foto_masuk',
                    'presensi_gurus.jam_pulang',
                    'presensi_gurus.foto_pulang',
                    'presensi_gurus.status',
                    'presensi_gurus.bukti_izin',
                    'presensi_gurus.created_at',
                    'presensi_gurus.updated_at',
                ])
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_presensi_guru_logs');
    }
};
