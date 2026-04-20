<?php

namespace App\Filament\Siswa\Resources\PresensiDetails\Pages;

use App\Filament\Siswa\Resources\PresensiDetails\PresensiDetailResource;
use App\Models\Jadwal;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ListPresensiDetails extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = PresensiDetailResource::class;

    protected string $view = 'resources.pages.page';

    public function mount(): void
    {
        abort_if(! Auth::check() || Auth::user()->role !== 'siswa', 403);
    }

    public function getTitle(): string
    {
        return 'Presensi';
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
            ])
            ->recordActions([
                Action::make('lihatJadwal')
                    ->label('Lihat Jadwal')
                    ->icon('heroicon-o-eye')
                    ->url(fn (array $record) => PresensiDetailResource::getUrl('hari', [
                        'hari' => $record['hari'],
                    ])),
            ])
            ->toolbarActions([]);
    }
}