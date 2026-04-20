<?php

namespace App\Filament\Guru\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RekapWaliKelas extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'resources.pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 30;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        $kelasNama = Auth::user()?->waliKelas?->nama;

        return $kelasNama
            ? "Rekap Pelajaran - {$kelasNama}"
            : 'Rekap Pelajaran';
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
                    ->state(fn (array $record): int => $this->countJadwalPerHari($record['hari']))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_sesi')
                    ->label('Total Sesi')
                    ->state(fn (array $record): int => $this->countSesiPerHari($record['hari']))
                    ->alignCenter(),
            ])
            ->recordActions([
                Action::make('lihatDetail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (array $record): string => RekapWaliKelasDetail::getUrl([
                        'hari' => $record['hari'],
                    ])),
            ])
            ->toolbarActions([]);
    }

    protected function countJadwalPerHari(string $hari): int
    {
        $kelasId = Auth::user()?->wali_kelas_id;

        return \App\Models\Jadwal::query()
            ->where('kelas_id', $kelasId)
            ->where('hari', $hari)
            ->count();
    }

    protected function countSesiPerHari(string $hari): int
    {
        $kelasId = Auth::user()?->wali_kelas_id;

        return \App\Models\PresensiSesi::query()
            ->whereHas('jadwal', function ($query) use ($kelasId, $hari) {
                $query->where('kelas_id', $kelasId)
                    ->where('hari', $hari);
            })
            ->count();
    }
}