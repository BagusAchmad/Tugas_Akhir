<?php

namespace App\Filament\Resources\Jurusans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class JurusanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama Jurusan')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            TextInput::make('singkatan')
                ->label('Singkatan')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true),

            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
        ]);
    }
}