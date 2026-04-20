<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelas extends Model
{
    use SoftDeletes;

    protected $table = 'kelas';

    protected $fillable = [
        'tingkat',
        'jurusan',
        'nomor',
        'nama',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'nomor' => 'integer',
    ];

    public function siswas(): HasMany
    {
        return $this->hasMany(\App\Models\User::class, 'kelas_id')
            ->where('role', 'siswa');
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(\App\Models\Jadwal::class, 'kelas_id');
    }
}