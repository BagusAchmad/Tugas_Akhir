<?php

namespace App\Filament\Resources\Mapels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MapelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->recordUrl(null)
            ->defaultSort('nama', 'asc')

            ->columns([
                TextColumn::make('row_index')
                    ->label('No')
                    ->state(fn ($rowLoop) => $rowLoop->iteration),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kode')
                    ->label('Kode')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()->label('Edit'),
                DeleteAction::make()->label('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Hapus terpilih'),
                ]),
            ]);
    }
}