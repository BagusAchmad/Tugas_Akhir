<?php

namespace App\Filament\Widgets;

use App\Models\Jadwal;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AdminJadwalHariIni extends TableWidget
{
    protected static ?string $heading = 'Jadwal Hari Ini';

    protected int|string|array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->defaultSort('jam_ke')
            ->columns([
                TextColumn::make('kelas.nama')
                    ->label('Rombel')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jam_ke')
                    ->label('Jam Ke')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('mapel.nama')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('guru.name')
                    ->label('Guru')
                    ->searchable()
                    ->sortable(),
            ])
            ->emptyStateHeading('Belum ada jadwal hari ini')
            ->emptyStateDescription('Jadwal aktif yang berlaku untuk hari ini belum ditemukan.');
    }

    protected function getTableQuery(): Builder
    {
        $hariIni = Carbon::today();

        return Jadwal::query()
            ->with(['kelas', 'mapel', 'guru'])
            ->where('aktif', true)
            ->where('hari', $this->getHariIni())
            ->where(function ($query) use ($hariIni) {
                $query->whereNull('berlaku_dari')
                    ->orWhereDate('berlaku_dari', '<=', $hariIni);
            })
            ->where(function ($query) use ($hariIni) {
                $query->whereNull('berlaku_sampai')
                    ->orWhereDate('berlaku_sampai', '>=', $hariIni);
            });
    }

    protected function getHariIni(): string
    {
        return match (strtolower(Carbon::today()->locale('id')->dayName)) {
            'senin' => 'senin',
            'selasa' => 'selasa',
            'rabu' => 'rabu',
            'kamis' => 'kamis',
            'jumat' => 'jumat',
            default => strtolower(Carbon::today()->translatedFormat('l')),
        };
    }
}