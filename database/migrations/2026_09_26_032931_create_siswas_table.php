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
    Schema::create('siswas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->string('nisn')->unique();
        $table->string('nama_lengkap');
        $table->foreignId('kelas_id')->nullable()->constrained('kelas')->onDelete('set null');
        $table->enum('status', ['aktif', 'alumni', 'pindah'])->default('aktif');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
