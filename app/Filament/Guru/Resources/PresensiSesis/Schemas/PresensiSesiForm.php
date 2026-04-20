<?php

namespace App\Filament\Guru\Resources\PresensiSesis\Schemas;

use App\Models\Jadwal;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PresensiSesiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('jadwal_id')
                ->label('Jadwal')
                ->required()
                ->options(
                    Jadwal::query()
                        ->where('guru_id', Auth::id())
                        ->orderBy('kelas_id')
                        ->orderBy('hari')
                        ->orderBy('jam_ke')
                        ->get()
                        ->mapWithKeys(function ($j) {
                            $hari = ucfirst($j->hari);
                            $label = "{$j->kelas->nama} - {$hari} (Jam {$j->jam_ke}) - {$j->mapel->nama}";
                            return [$j->id => $label];
                        })
                        ->toArray()
                )
                ->searchable(),

            DatePicker::make('tanggal')
                ->label('Tanggal')
                ->required()
                ->default(now()->toDateString()),

            Textarea::make('catatan')
                ->label('Catatan / Materi (opsional)')
                ->nullable()
                ->columnSpanFull(),
        ]);
    }
}