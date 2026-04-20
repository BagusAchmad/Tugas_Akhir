<?php

namespace App\Filament\Resources\Mapels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MapelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama Mata Pelajaran')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            TextInput::make('kode')
                ->label('Kode')
                ->nullable()
                ->maxLength(50)
                ->unique(ignoreRecord: true),

            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
        ]);
    }
}