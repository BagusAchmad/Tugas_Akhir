<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    protected $table = 'mapels';

    protected $fillable = [
        'nama',
        'kode',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];
}