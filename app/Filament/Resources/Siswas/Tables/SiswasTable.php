<?php

namespace App\Filament\Resources\Siswas\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SiswasTable
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
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kelas.nama')
                    ->label('Kelas')
                    ->placeholder('-')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('keterangan_nonaktif')
                    ->label('Keterangan')
                    ->state(fn ($record) => $record->is_active ? '-' : ($record->keterangan_nonaktif ?? '-'))
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),

                SelectFilter::make('kelas_id')
                    ->label('Kelas/Rombel')
                    ->relationship('kelas', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()->label('Edit'),
            ])

            ->toolbarActions([
                // Kept empty to avoid unsafe deletion of students
            ]);
    }
}