<?php

namespace App\Filament\Resources\Siswas\Schemas;

use App\Models\Kelas;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Siswa')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Siswa')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('nis')
                        ->label('NIS')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->helperText('NIS dipakai untuk login. Password default = NIS.'),

                    Select::make('kelas_id')
                        ->label('Kelas')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(fn () => Kelas::query()
                            ->where('aktif', true)
                            ->orderBy('tingkat')
                            ->orderBy('jurusan')
                            ->orderBy('nomor')
                            ->pluck('nama', 'id')
                        ),
                ]),
        ]);
    }
}