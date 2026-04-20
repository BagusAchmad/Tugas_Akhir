<?php

namespace App\Filament\Resources\Gurus\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GurusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->recordUrl(null)
            ->defaultSort('name', 'asc')

            ->columns([
                TextColumn::make('row_index')
                    ->label('No')
                    ->state(fn ($rowLoop) => $rowLoop->iteration),

                TextColumn::make('name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('waliKelas.nama')
                    ->label('Wali Kelas')
                    ->placeholder('-')
                    ->sortable(),
            ])

            ->recordActions([
                EditAction::make()->label('Edit'),
                DeleteAction::make()->label('Hapus'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus terpilih'),
                ]),
            ]);
    }
}