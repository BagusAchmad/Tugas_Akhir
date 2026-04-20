<?php

namespace App\Filament\Resources\RekapAbsensis\Pages;

use App\Filament\Resources\RekapAbsensis\RekapAbsensiResource;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\PresensiSesi;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ViewRekapAbsensiKelas extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = RekapAbsensiResource::class;

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
        return 'Rekap Absensi - ' . ($this->kelas->nama ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(RekapAbsensiResource::getUrl('index')),
        ];
    }

    public function getSubheading(): ?string
    {
        $waliKelas = User::query()
            ->where('role', 'guru')
            ->where('wali_kelas_id', $this->kelas?->id)
            ->value('name') ?? '-';

        $jumlahSiswa = User::query()
            ->where('role', 'siswa')
            ->where('kelas_id', $this->kelas?->id)
            ->count();

        $jadwalAktif = Jadwal::query()
            ->where('kelas_id', $this->kelas?->id)
            ->where('aktif', true)
            ->count();

        $totalPertemuan = PresensiSesi::query()
            ->whereHas('jadwal', function ($query) {
                $query->where('kelas_id', $this->kelas?->id);
            })
            ->count();

        $sudahDibuka = PresensiSesi::query()
            ->whereHas('jadwal', function ($query) {
                $query->where('kelas_id', $this->kelas?->id);
            })
            ->whereIn('status', ['open', 'closed'])
            ->count();

        return "Wali Kelas: {$waliKelas} | Jumlah Siswa: {$jumlahSiswa} | Jadwal Aktif: {$jadwalAktif} | Progress: {$sudahDibuka} / {$totalPertemuan}";
    }

    public function table(Table $table): Table
    {
        $rows = collect([
            ['id' => 1, 'hari' => 'senin'],
            ['id' => 2, 'hari' => 'selasa'],
            ['id' => 3, 'hari' => 'rabu'],
            ['id' => 4, 'hari' => 'kamis'],
            ['id' => 5, 'hari' => 'jumat'],
        ])->map(function (array $row) {
            return [
                ...$row,
                'jumlah_jadwal' => $this->hitungJumlahJadwal($row['hari']),
                'total_pertemuan' => $this->hitungTotalPertemuan($row['hari']),
                'sudah_dibuka' => $this->hitungSudahDibuka($row['hari']),
                'progress' => $this->hitungSudahDibuka($row['hari']) . ' / ' . $this->hitungTotalPertemuan($row['hari']),
            ];
        });

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
                    ->formatStateUsing(fn (?string $state) => $this->formatHari($state)),

                Tables\Columns\TextColumn::make('jumlah_jadwal')
                    ->label('Jumlah Jadwal')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_pertemuan')
                    ->label('Total Pertemuan')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sudah_dibuka')
                    ->label('Sudah Dibuka')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->badge(),
            ])
            ->recordActions([
                Action::make('lihatRekapHari')
                    ->label('Lihat Rekap')
                    ->icon('heroicon-o-eye')
                    ->url(fn (array $record) => RekapAbsensiResource::getUrl('hari', [
                        'record' => $this->kelas,
                        'hari' => $record['hari'],
                    ])),
            ])
            ->toolbarActions([]);
    }

    protected function hitungJumlahJadwal(string $hari): int
    {
        return Jadwal::query()
            ->where('kelas_id', $this->kelas?->id)
            ->where('hari', $hari)
            ->where('aktif', true)
            ->count();
    }

    protected function hitungTotalPertemuan(string $hari): int
    {
        return PresensiSesi::query()
            ->whereHas('jadwal', function ($query) use ($hari) {
                $query->where('kelas_id', $this->kelas?->id)
                    ->where('hari', $hari);
            })
            ->count();
    }

    protected function hitungSudahDibuka(string $hari): int
    {
        return PresensiSesi::query()
            ->whereHas('jadwal', function ($query) use ($hari) {
                $query->where('kelas_id', $this->kelas?->id)
                    ->where('hari', $hari);
            })
            ->whereIn('status', ['open', 'closed'])
            ->count();
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