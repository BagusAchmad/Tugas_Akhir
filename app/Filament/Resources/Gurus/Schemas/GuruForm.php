<?php

namespace App\Filament\Resources\Gurus\Schemas;

use App\Models\Kelas;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class GuruForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Akun Guru')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Nama Guru')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn ($state) => strtolower(trim((string) $state))),

                        TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->minLength(8)
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->minLength(8)
                            ->same('password')
                            ->dehydrated(false),

                        Select::make('wali_kelas_id')
                            ->label('Wali Kelas (opsional)')
                            ->options(
                                Kelas::query()
                                    ->where('aktif', true)
                                    ->orderBy('nama')
                                    ->pluck('nama', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Kosongkan jika guru bukan wali kelas.'),

                        TextInput::make('role')
                            ->default('guru')
                            ->dehydrated()
                            ->hidden(),
                    ]),
                ]),
        ]);
    }
}