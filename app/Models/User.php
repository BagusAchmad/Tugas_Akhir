<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'username',
        'nis',
        'wali_kelas_id',
        'kelas_id',
        'is_active',
        'keterangan_nonaktif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->role === 'admin',
            'guru'  => $this->role === 'guru',
            'siswa' => $this->role === 'siswa' && $this->is_active === true,
            default => false,
        };
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'wali_kelas_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function presensiDetails(): HasMany
    {
        return $this->hasMany(PresensiDetail::class, 'siswa_id');
    }
}