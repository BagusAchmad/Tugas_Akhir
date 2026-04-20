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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('siswa')->after('password'); // admin|guru|siswa
            $table->string('username')->nullable()->unique()->after('role'); // untuk admin (bebas)
            $table->string('nis')->nullable()->unique()->after('username'); // untuk siswa
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropUnique(['nis']);

            $table->dropColumn(['role', 'username', 'nis']);
        });
    }
};