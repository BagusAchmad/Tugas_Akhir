<?php

namespace App\Filament\Guru\Resources\PresensiSesis\Pages;

use App\Filament\Guru\Resources\PresensiSesis\PresensiSesiResource;
use App\Models\Jadwal;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListPresensiKelasMapel extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = PresensiSesiResource::class;

    protected string $view = 'resources.pages.page';

    public ?string $hari = null;

    public function mount(string $hari): void
    {
        $this->hari = strtolower($hari);

        abort_if(! Auth::check() || Auth::user()->role !== 'guru', 403);
        abort_if(! in_array($this->hari, ['senin', 'selasa', 'rabu', 'kamis', 'jumat']), 404);
    }

    public function getTitle(): string
    {
        return 'Presensi - ' . $this->formatHari($this->hari);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(PresensiSesiResource::getUrl('index')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->recordAction(null)
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->extraHeaderAttributes(['style' => 'text-align: center;'])
                    ->extraCellAttributes(['style' => 'text-align: center;']),

                Tables\Columns\TextColumn::make('kelas.tingkat')
                    ->label('Tingkat')
                    ->sortable()
                    ->extraHeaderAttributes(['style' => 'text-align: center;'])
                    ->extraCellAttributes(['style' => 'text-align: center;']),

                Tables\Columns\TextColumn::make('kelas.jurusan')
                    ->label('Jurusan')
                    ->sortable()
                    ->extraHeaderAttributes(['style' => 'text-align: center;'])
                    ->extraCellAttributes(['style' => 'text-align: center;']),

                Tables\Columns\TextColumn::make('kelas.nomor')
                    ->label('Nomor')
                    ->sortable()
                    ->extraHeaderAttributes(['style' => 'text-align: center;'])
                    ->extraCellAttributes(['style' => 'text-align: center;']),

                Tables\Columns\TextColumn::make('kelas.nama')
                    ->label('Nama Kelas')
                    ->searchable()
                    ->sortable()
                    ->extraHeaderAttributes(['style' => 'text-align: center;'])
                    ->extraCellAttributes(['style' => 'text-align: center;']),

                Tables\Columns\TextColumn::make('mapel.nama')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('kelolaPresensi')
                    ->label('Kelola Presensi')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn (Jadwal $record) => PresensiSesiResource::getUrl('view', [
                        'jadwal' => $record->id,
                    ])),
            ])
            ->toolbarActions([]);
    }

    protected function getTableQuery(): Builder
    {
        return Jadwal::query()
            ->with(['kelas', 'mapel'])
            ->where('guru_id', Auth::id())
            ->where('hari', $this->hari)
            ->where('aktif', true)
            ->orderByRaw("
                CASE (
                    SELECT tingkat
                    FROM kelas
                    WHERE kelas.id = jadwals.kelas_id
                    LIMIT 1
                )
                    WHEN 'X' THEN 1
                    WHEN 'XI' THEN 2
                    WHEN 'XII' THEN 3
                    ELSE 99
                END
            ")
            ->orderBy(
                \App\Models\Kelas::query()
                    ->select('jurusan')
                    ->whereColumn('kelas.id', 'jadwals.kelas_id')
                    ->limit(1)
            )
            ->orderBy(
                \App\Models\Kelas::query()
                    ->select('nomor')
                    ->whereColumn('kelas.id', 'jadwals.kelas_id')
                    ->limit(1)
            )
            ->orderBy('jam_ke');
    }

    protected function formatHari(?string $hari): string
    {
        return match ($hari) {
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            default => ucfirst((string) $hari),
        };
    }
}