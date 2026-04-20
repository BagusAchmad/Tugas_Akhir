<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    protected $table = 'jurusans';

    protected $fillable = [
        'nama',
        'singkatan',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];
}