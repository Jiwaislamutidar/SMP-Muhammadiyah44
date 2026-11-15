<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_presensi_guru_session_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_presensi_id')->nullable();
            $table->unsignedBigInteger('guru_id');
            $table->string('guru_nama');
            $table->date('tanggal');
            $table->enum('session_type', ['masuk', 'pulang']);
            $table->time('jam')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();
            $table->unique(['source_presensi_id', 'session_type'], 'dpgsl_source_session_uq');
            $table->index(['tanggal', 'session_type'], 'dpgsl_date_type_ix');
        });

        DB::table('dashboard_presensi_guru_logs')
            ->orderBy('id')
            ->chunk(500, function ($attendances): void {
                $sessions = [];

                foreach ($attendances as $attendance) {
                    if ($attendance->jam_masuk || $attendance->foto_masuk) {
                        $sessions[] = [
                            'source_presensi_id' => $attendance->source_presensi_id,
                            'guru_id' => $attendance->guru_id,
                            'guru_nama' => $attendance->guru_nama,
                            'tanggal' => $attendance->tanggal,
                            'session_type' => 'masuk',
                            'jam' => $attendance->jam_masuk,
                            'foto' => $attendance->foto_masuk,
                            'created_at' => $attendance->created_at,
                            'updated_at' => $attendance->updated_at,
                        ];
                    }

                    if ($attendance->jam_pulang || $attendance->foto_pulang) {
                        $sessions[] = [
                            'source_presensi_id' => $attendance->source_presensi_id,
                            'guru_id' => $attendance->guru_id,
                            'guru_nama' => $attendance->guru_nama,
                            'tanggal' => $attendance->tanggal,
                            'session_type' => 'pulang',
                            'jam' => $attendance->jam_pulang,
                            'foto' => $attendance->foto_pulang,
                            'created_at' => $attendance->created_at,
                            'updated_at' => $attendance->updated_at,
                        ];
                    }
                }

                if ($sessions !== []) {
                    DB::table('dashboard_presensi_guru_session_logs')->insert($sessions);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_presensi_guru_session_logs');
    }
};
