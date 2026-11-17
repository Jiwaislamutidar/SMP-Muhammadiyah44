<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->decimal('school_latitude', 10, 7)->default(-6.342156)->after('jam_pulang_jumat');
            $table->decimal('school_longitude', 10, 7)->default(106.738123)->after('school_latitude');
            $table->unsignedInteger('max_distance_meters')->default(75)->after('school_longitude');
        });
        foreach (['presensi_gurus', 'dashboard_presensi_guru_logs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->decimal('jarak_meter', 10, 2)->nullable();
            });
        }
        if (Schema::hasTable('presensi_guru_mapels')) {
            Schema::table('presensi_guru_mapels', function (Blueprint $table): void {
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->decimal('jarak_meter', 10, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['presensi_gurus', 'dashboard_presensi_guru_logs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['latitude', 'longitude', 'jarak_meter']);
            });
        }
        if (Schema::hasTable('presensi_guru_mapels')) {
            Schema::table('presensi_guru_mapels', function (Blueprint $table): void {
                $table->dropColumn(['latitude', 'longitude', 'jarak_meter']);
            });
        }
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn(['school_latitude', 'school_longitude', 'max_distance_meters']);
        });
    }
};
