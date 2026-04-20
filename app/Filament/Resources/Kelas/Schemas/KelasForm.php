<?php

namespace App\Filament\Resources\Kelas\Schemas;

use App\Models\Jurusan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class KelasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Select::make('tingkat')
                    ->label('Tingkat')
                    ->options([
                        'X' => 'X',
                        'XI' => 'XI',
                        'XII' => 'XII',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::setNama($get, $set)),

                Select::make('jurusan')
                    ->label('Jurusan')
                    ->options(fn () => Jurusan::query()
                        ->where('aktif', true)
                        ->orderBy('singkatan')
                        ->get()
                        ->mapWithKeys(fn (Jurusan $jurusan) => [
                            $jurusan->singkatan => $jurusan->singkatan . ' - ' . $jurusan->nama,
                        ])
                        ->toArray())
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::setNama($get, $set)),

                TextInput::make('nomor')
                    ->label('Nomor')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::setNama($get, $set)),

                TextInput::make('nama')
                    ->label('Nama Kelas')
                    ->placeholder('Kelas')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->unique(ignoreRecord: true),
            ]),

            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    protected static function setNama(Get $get, Set $set): void
    {
        $tingkat = $get('tingkat');
        $jurusan = $get('jurusan');
        $nomor = $get('nomor');

        if (! $tingkat || ! $jurusan || ! $nomor) {
            $set('nama', null);
            return;
        }

        $set('nama', trim($tingkat . ' ' . $jurusan . ' ' . $nomor));
    }
}