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
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('tingkat', 10);
            $table->string('jurusan', 50);
            $table->unsignedTinyInteger('nomor');
            $table->string('nama', 80)->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tingkat', 'jurusan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
