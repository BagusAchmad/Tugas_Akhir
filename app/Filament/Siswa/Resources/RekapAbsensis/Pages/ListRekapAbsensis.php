<?php

namespace App\Filament\Siswa\Resources\RekapAbsensis\Pages;

use App\Filament\Siswa\Resources\RekapAbsensis\RekapAbsensiResource;
use App\Models\Jadwal;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ListRekapAbsensis extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = RekapAbsensiResource::class;

    protected string $view = 'resources.pages.page';

    public function mount(): void
    {
        abort_if(! Auth::check() || Auth::user()->role !== 'siswa', 403);
    }

    public function getTitle(): string
    {
        return 'Rekap Absensi';
    }

    public function table(Table $table): Table
    {
        $rows = collect([
            ['id' => 1, 'hari' => 'senin'],
            ['id' => 2, 'hari' => 'selasa'],
            ['id' => 3, 'hari' => 'rabu'],
            ['id' => 4, 'hari' => 'kamis'],
            ['id' => 5, 'hari' => 'jumat'],
        ]);

        return $table
            ->records(fn (): Collection => $rows)
            ->recordAction(null)
            ->recordUrl(null)
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No'),

                Tables\Columns\TextColumn::make('hari')
                    ->label('Hari')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'senin' => 'Senin',
                        'selasa' => 'Selasa',
                        'rabu' => 'Rabu',
                        'kamis' => 'Kamis',
                        'jumat' => 'Jumat',
                        default => ucfirst((string) $state),
                    }),

                Tables\Columns\TextColumn::make('jumlah_jadwal')
                    ->label('Jumlah Jadwal')
                    ->state(function (array $record): string {
                        $jumlah = Jadwal::query()
                            ->where('kelas_id', Auth::user()->kelas_id)
                            ->where('hari', $record['hari'])
                            ->where('aktif', true)
                            ->count();

                        return $jumlah . ' Jadwal';
                    }),

                Tables\Columns\TextColumn::make('total_sesi')
                    ->label('Total Sesi')
                    ->state(fn (array $record): int => $this->hitungTotalSesiByHari($record['hari']))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('hadir')
                    ->label('Hadir')
                    ->state(fn (array $record): int => $this->hitungStatusByHari($record['hari'], 'hadir'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('izin')
                    ->label('Izin')
                    ->state(fn (array $record): int => $this->hitungStatusByHari($record['hari'], 'izin'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sakit')
                    ->label('Sakit')
                    ->state(fn (array $record): int => $this->hitungStatusByHari($record['hari'], 'sakit'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('alfa')
                    ->label('Alfa')
                    ->state(fn (array $record): int => $this->hitungStatusByHari($record['hari'], 'alfa'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('persentase_hadir')
                    ->label('% Hadir')
                    ->state(fn (array $record): string => $this->hitungPersentaseByHari($record['hari']))
                    ->badge(),
            ])
            ->recordActions([
                Action::make('lihatDetail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (array $record) => RekapAbsensiResource::getUrl('hari', [
                        'hari' => $record['hari'],
                    ])),
            ])
            ->toolbarActions([]);
    }

    protected function getSesiDibukaIdsByHari(string $hari)
    {
        return \App\Models\PresensiSesi::query()
            ->whereHas('jadwal', function ($query) use ($hari) {
                $query->where('kelas_id', Auth::user()->kelas_id)
                    ->where('hari', $hari)
                    ->where('aktif', true);
            })
            ->whereIn('status', ['open', 'closed'])
            ->pluck('id');
    }

    protected function hitungTotalSesiByHari(string $hari): int
    {
        return $this->getSesiDibukaIdsByHari($hari)->count();
    }

    protected function hitungStatusByHari(string $hari, string $status): int
    {
        $sesiIds = $this->getSesiDibukaIdsByHari($hari);

        if ($sesiIds->isEmpty()) {
            return 0;
        }

        return \App\Models\PresensiDetail::query()
            ->where('siswa_id', Auth::id())
            ->whereIn('presensi_sesi_id', $sesiIds)
            ->where('status', $status)
            ->count();
    }

    protected function hitungPersentaseByHari(string $hari): string
    {
        $totalSesi = $this->hitungTotalSesiByHari($hari);
        $hadir = $this->hitungStatusByHari($hari, 'hadir');

        if ($totalSesi <= 0) {
            return '0%';
        }

        return round(($hadir / $totalSesi) * 100, 2) . '%';
    }
}