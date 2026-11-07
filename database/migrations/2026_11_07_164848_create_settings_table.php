<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->time('jam_masuk')->default('07:00:00');
            $table->time('jam_toleransi')->default('07:15:00');
            $table->time('jam_pulang_senin')->default('14:30:00');
            $table->time('jam_pulang_selasa')->default('13:00:00');
            $table->time('jam_pulang_rabu')->default('14:30:00');
            $table->time('jam_pulang_kamis')->default('13:00:00');
            $table->time('jam_pulang_jumat')->default('12:30:00');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};