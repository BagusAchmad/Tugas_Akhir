<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('presensi_sesi_id')
                ->constrained('presensi_sesis')
                ->cascadeOnDelete();
            $table->foreignId('siswa_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alfa'])->default('alfa');
            $table->text('keterangan')->nullable();
            $table->enum('metode', ['siswa', 'guru'])->default('siswa');
            $table->dateTime('waktu_isi')->nullable();
            $table->timestamps();
            $table->unique(['presensi_sesi_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_details');
    }
};