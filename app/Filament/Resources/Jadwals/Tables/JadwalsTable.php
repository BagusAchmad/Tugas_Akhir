<?php

namespace App\Filament\Resources\Jadwals\Tables;

use App\Filament\Resources\Jadwals\JadwalResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class JadwalsTable
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
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tingkat')
                    ->label('Tingkat')
                    ->options([
                        'X' => 'X',
                        'XI' => 'XI',
                        'XII' => 'XII',
                    ])
                    ->native(false),

                SelectFilter::make('jurusan')
                    ->label('Jurusan')
                    ->options([
                        'PPLG' => 'PPLG',
                        'MPLB' => 'MPLB',
                    ])
                    ->native(false),

                TernaryFilter::make('aktif')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                Action::make('detailJadwal')
                    ->label('Detail Jadwal')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn ($record) => JadwalResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }
}