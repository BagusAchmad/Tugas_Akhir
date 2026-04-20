<?php

namespace App\Filament\Guru\Resources\RekapAbsensis\Tables;

use App\Filament\Guru\Resources\RekapAbsensis\RekapAbsensiResource;
use App\Models\PresensiSesi;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RekapAbsensisTable
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

                TextColumn::make('kelas.nama')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mapel.nama')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('progress_pertemuan')
                    ->label('Progress Pertemuan')
                    ->state(function ($record): string {
                        $totalPertemuan = PresensiSesi::query()
                            ->where('jadwal_id', $record->id)
                            ->count();

                        $sudahDibuka = PresensiSesi::query()
                            ->where('jadwal_id', $record->id)
                            ->whereIn('status', ['open', 'closed'])
                            ->count();

                        return $sudahDibuka . ' / ' . $totalPertemuan;
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('lihatRekap')
                    ->label('Lihat Rekap')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => RekapAbsensiResource::getUrl('view', [
                        'record' => $record,
                    ])),
            ])
            ->toolbarActions([
                //
            ]);
    }
}