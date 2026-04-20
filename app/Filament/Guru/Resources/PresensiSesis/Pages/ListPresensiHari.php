<?php

namespace App\Filament\Guru\Resources\PresensiSesis\Pages;

use App\Filament\Guru\Resources\PresensiSesis\PresensiSesiResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ListPresensiHari extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = PresensiSesiResource::class;

    protected static ?string $title = 'Presensi';

    protected string $view = 'resources.pages.page';

    public function mount(): void
    {
        abort_if(! Auth::check() || Auth::user()->role !== 'guru', 403);
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
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'senin' => 'Senin',
                        'selasa' => 'Selasa',
                        'rabu' => 'Rabu',
                        'kamis' => 'Kamis',
                        'jumat' => 'Jumat',
                        default => ucfirst((string) $state),
                    }),
            ])
            ->recordActions([
                Action::make('lihatKelas')
                    ->label('Lihat Kelas')
                    ->icon('heroicon-o-eye')
                    ->url(fn (array $record) => PresensiSesiResource::getUrl('hari', [
                        'hari' => $record['hari'],
                    ])),
            ])
            ->toolbarActions([]);
    }
}