<?php

namespace App\Filament\Resources\Jadwals\Pages;

use App\Filament\Resources\Jadwals\JadwalResource;
use App\Models\Kelas;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ViewJadwalKelas extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = JadwalResource::class;

    protected string $view = 'resources.pages.page';

    public ?int $record = null;

    public ?Kelas $kelas = null;

    public function mount($record): void
    {
        $this->record = (int) $record;
        $this->kelas = Kelas::findOrFail($this->record);
    }

    public function getTitle(): string
    {
        return 'Detail Jadwal - ' . ($this->kelas->nama ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(JadwalResource::getUrl('index')),
        ];
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
                Action::make('kelolaJadwal')
                    ->label('Kelola Jadwal')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn (array $record) => JadwalResource::getUrl('hari', [
                        'record' => $this->record,
                        'hari' => $record['hari'],
                    ])),
            ])
            ->toolbarActions([]);
    }
}