<?php

namespace App\Filament\Widgets;

use App\Models\PresensiSesi;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AdminSesiBerjalan extends TableWidget
{
    protected static ?string $heading = 'Sesi Masih Berjalan';

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->defaultSort('dibuka_pada', 'desc')
            ->columns([
                TextColumn::make('jadwal.kelas.nama')
                    ->label('Rombel')
                    ->searchable(),

                TextColumn::make('jadwal.jam_ke')
                    ->label('Jam Ke')
                    ->alignCenter(),

                TextColumn::make('jadwal.mapel.nama')
                    ->label('Mata Pelajaran')
                    ->searchable(),

                TextColumn::make('dibuka_pada')
                    ->label('Dibuka Pada')
                    ->since()
                    ->alignCenter(),

                TextColumn::make('progress')
                    ->label('Progress')
                    ->state(function (PresensiSesi $record): string {
                        $totalIsi = $record->details_count ?? $record->details()->count();
                        $totalSiswa = $record->jadwal?->kelas?->siswas()->count() ?? 0;

                        return $totalIsi . ' / ' . $totalSiswa;
                    })
                    ->alignCenter(),
            ])
            ->emptyStateHeading('Tidak ada sesi berjalan')
            ->emptyStateDescription('Semua sesi hari ini sudah ditutup atau belum ada yang dibuka.');
    }

    protected function getTableQuery(): Builder
    {
        return PresensiSesi::query()
            ->with([
                'jadwal.kelas',
                'jadwal.mapel',
            ])
            ->withCount('details')
            ->whereDate('tanggal', Carbon::today())
            ->whereNotNull('dibuka_pada')
            ->whereNull('ditutup_pada');
    }
}