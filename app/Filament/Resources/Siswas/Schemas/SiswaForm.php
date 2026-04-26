<?php

namespace App\Filament\Resources\Siswas\Schemas;

use App\Models\Kelas;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Utilities\Get;
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
                        ->helperText('NIS dipakai untuk login. Password default = NIS.')
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('username', $state);
                        }),

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

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->live(),

                    Select::make('keterangan_nonaktif')
                        ->label('Keterangan Nonaktif')
                        ->options([
                            'Lulus' => 'Lulus',
                            'Pindah Sekolah' => 'Pindah Sekolah',
                            'Mengundurkan Diri' => 'Mengundurkan Diri',
                            'Dikeluarkan' => 'Dikeluarkan',
                            'Lainnya' => 'Lainnya',
                        ])
                        ->hidden(fn (Get $get) => $get('is_active') === true)
                        ->required(fn (Get $get) => $get('is_active') === false)
                        ->dehydrateStateUsing(fn ($state, Get $get) => $get('is_active') === true ? null : $state),

                    Hidden::make('username'),
                ]),
        ]);
    }
}