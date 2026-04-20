<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiDetail extends Model
{
    protected $table = 'presensi_details';

    protected $fillable = [
        'presensi_sesi_id',
        'siswa_id',
        'status',
        'keterangan',
        'metode',
        'waktu_isi',
    ];

    protected $casts = [
        'waktu_isi' => 'datetime',
    ];

    public function sesi()
    {
        return $this->belongsTo(PresensiSesi::class, 'presensi_sesi_id');
    }

    public function siswa()
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
}