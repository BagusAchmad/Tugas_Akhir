<?php

namespace App\Filament\Guru\Resources\Jadwals\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class JadwalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kelas_id')
                    ->required()
                    ->numeric(),
                TextInput::make('mapel_id')
                    ->required()
                    ->numeric(),
                TextInput::make('guru_id')
                    ->required()
                    ->numeric(),
                Select::make('hari')
                    ->options([
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            'sabtu' => 'Sabtu',
        ])
                    ->required(),
                TextInput::make('jam_ke')
                    ->required()
                    ->numeric(),
                Toggle::make('aktif')
                    ->required(),
            ]);
    }
}
