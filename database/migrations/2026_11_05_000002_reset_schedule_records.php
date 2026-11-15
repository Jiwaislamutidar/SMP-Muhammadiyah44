<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('presensi_pelajarans')->delete();
            DB::table('sesi_pelajarans')->delete();
            DB::table('jadwal_pelajarans')->delete();
        });
    }

    public function down(): void
    {
        // Schedule and attendance rows removed by this migration cannot be restored.
    }
};
