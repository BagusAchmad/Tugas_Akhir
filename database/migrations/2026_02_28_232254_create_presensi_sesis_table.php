<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_sesis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwals')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->dateTime('dibuka_pada')->nullable();
            $table->foreignId('dibuka_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('ditutup_pada')->nullable();
            $table->foreignId('ditutup_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['jadwal_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_sesis');
    }
};