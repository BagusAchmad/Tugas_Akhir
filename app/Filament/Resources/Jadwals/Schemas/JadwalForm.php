<?php

namespace App\Filament\Resources\Jadwals\Schemas;

use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class JadwalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kelas_id')
                ->label('Rombel')
                ->required()
                ->searchable()
                ->preload()
                ->options(
                    Kelas::query()
                        ->where('aktif', true)
                        ->orderByRaw("
                            CASE tingkat
                                WHEN 'X' THEN 1
                                WHEN 'XI' THEN 2
                                WHEN 'XII' THEN 3
                                ELSE 99
                            END
                        ")
                        ->orderBy('jurusan')
                        ->orderBy('nomor')
                        ->pluck('nama', 'id')
                        ->toArray()
                ),

            Select::make('hari')
                ->label('Hari')
                ->required()
                ->options([
                    'senin' => 'Senin',
                    'selasa' => 'Selasa',
                    'rabu' => 'Rabu',
                    'kamis' => 'Kamis',
                    'jumat' => 'Jumat',
                ])
                ->native(false),

            TextInput::make('jam_ke')
                ->label('Jam Ke')
                ->required()
                ->numeric()
                ->minValue(1)
                ->maxValue(20)
                ->rule(function ($record) {
                    return (new Unique('jadwals', 'jam_ke'))
                        ->where(fn ($query, $get) => $query
                            ->where('kelas_id', $get('kelas_id'))
                            ->where('hari', $get('hari'))
                        )
                        ->ignore($record);
                })
                ->validationMessages([
                    'unique' => 'Jam ke untuk rombel dan hari tersebut sudah dipakai.',
                ]),

            Select::make('mapel_id')
                ->label('Mata Pelajaran')
                ->required()
                ->searchable()
                ->preload()
                ->options(
                    Mapel::query()
                        ->where('aktif', true)
                        ->orderBy('nama')
                        ->pluck('nama', 'id')
                        ->toArray()
                ),

            Select::make('guru_id')
                ->label('Guru')
                ->required()
                ->searchable()
                ->preload()
                ->options(
                    User::query()
                        ->where('role', 'guru')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                ),

            DatePicker::make('berlaku_dari')
                ->label('Berlaku Dari')
                ->native(false)
                ->displayFormat('d/m/Y'),

            DatePicker::make('berlaku_sampai')
                ->label('Berlaku Sampai')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->rule('after_or_equal:berlaku_dari')
                ->validationMessages([
                    'after_or_equal' => 'Berlaku sampai harus sama atau setelah berlaku dari.',
                ]),

            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
        ]);
    }
}