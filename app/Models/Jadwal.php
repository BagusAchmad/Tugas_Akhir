<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jadwal extends Model
{
    protected $table = 'jadwals';

    protected $fillable = [
        'kelas_id',
        'mapel_id',
        'guru_id',
        'hari',
        'jam_ke',
        'aktif',
        'berlaku_dari',
        'berlaku_sampai',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'jam_ke' => 'integer',
        'berlaku_dari' => 'date',
        'berlaku_sampai' => 'date',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}