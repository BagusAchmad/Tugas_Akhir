<?php

namespace App\Filament\Siswa\Resources\PresensiDetails\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PresensiDetailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('presensi_sesi_id')
                    ->required()
                    ->numeric(),
                TextInput::make('siswa_id')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alfa' => 'Alfa'])
                    ->default('alfa')
                    ->required(),
                Textarea::make('keterangan')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('metode')
                    ->options(['siswa' => 'Siswa', 'guru' => 'Guru'])
                    ->default('siswa')
                    ->required(),
                DateTimePicker::make('waktu_isi'),
            ]);
    }
}
