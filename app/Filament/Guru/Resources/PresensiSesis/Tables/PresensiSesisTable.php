<?php

namespace App\Filament\Guru\Resources\PresensiSesis\Tables;

use App\Filament\Guru\Resources\PresensiSesis\PresensiSesiResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PresensiSesisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->recordUrl(null)
            ->columns([
                TextColumn::make('row_index')
                    ->label('No')
                    ->state(fn ($rowLoop) => $rowLoop->iteration),

                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jurusan')
                    ->label('Jurusan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nomor')
                    ->label('Nomor')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('nama')
                    ->label('Nama Kelas')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('absensi')
                    ->label('Absensi')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn ($record) => PresensiSesiResource::getUrl('view', [
                        'record' => $record,
                    ])),
            ])
            ->toolbarActions([
                //
            ]);
    }
}