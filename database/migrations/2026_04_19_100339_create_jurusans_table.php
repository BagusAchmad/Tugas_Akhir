<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurusans', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255)->unique();
            $table->string('singkatan', 50)->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('jurusans')->insert([
            [
                'nama' => 'Pengembangan Perangkat Lunak dan Gim',
                'singkatan' => 'PPLG',
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Manajemen Perkantoran dan Layanan Bisnis',
                'singkatan' => 'MPLB',
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('jurusans');
    }
};