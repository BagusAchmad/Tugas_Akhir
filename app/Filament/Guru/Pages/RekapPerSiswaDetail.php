<?php

namespace App\Filament\Guru\Pages;

use App\Models\Jadwal;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RekapPerSiswaDetail extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected string $view = 'resources.pages.page';

    protected static ?string $slug = 'rekap-per-siswa-detail/{siswa}';

    public ?int $siswa = null;

    public ?User $siswaRecord = null;

    public function mount($siswa): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'guru', 403);
        abort_unless(filled(Auth::user()?->wali_kelas_id), 403);

        $this->siswa = (int) $siswa;

        $this->siswaRecord = User::query()
            ->where('role', 'siswa')
            ->where('kelas_id', Auth::user()->wali_kelas_id)
            ->findOrFail($this->siswa);

        $this->syncSemuaJadwal();
    }

    public function getTitle(): string
    {
        return 'Detail Rekap Siswa - ' . ($this->siswaRecord?->name ?? '-');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(RekapPerSiswa::getUrl()),
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
        ])->map(function (array $row) {
            return [
                ...$row,
                'total_sesi' => $this->hitungTotalSesiByHari($row['hari']),
                'hadir' => $this->hitungStatusByHari($row['hari'], 'hadir'),
                'izin' => $this->hitungStatusByHari($row['hari'], 'izin'),
                'sakit' => $this->hitungStatusByHari($row['hari'], 'sakit'),
                'alfa' => $this->hitungStatusByHari($row['hari'], 'alfa'),
                'persentase_hadir' => $this->hitungPersentaseByHari($row['hari']),
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

                Tables\Columns\TextColumn::make('total_sesi')
                    ->label('Total Sesi')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('hadir')
                    ->label('Hadir')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('izin')
                    ->label('Izin')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sakit')
                    ->label('Sakit')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('alfa')
                    ->label('Alfa')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('persentase_hadir')
                    ->label('% Hadir')
                    ->badge(),
            ])
            ->recordActions([
                Action::make('lihatDetail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (array $record): string => RekapPerSiswaHari::getUrl([
                        'siswa' => $this->siswaRecord->id,
                        'hari' => $record['hari'],
                    ])),
            ])
            ->toolbarActions([]);
    }

    protected function syncSemuaJadwal(): void
    {
        $kelasId = Auth::user()?->wali_kelas_id;

        $jadwals = Jadwal::query()
            ->where('kelas_id', $kelasId)
            ->get();

        foreach ($jadwals as $jadwal) {
            $this->syncPresensiSesi($jadwal);
        }
    }

    protected function syncPresensiSesi(Jadwal $jadwal): void
    {
        if (! $jadwal->berlaku_dari || ! $jadwal->berlaku_sampai) {
            return;
        }

        $tanggalList = $this->generateTanggalSesi(
            $jadwal->hari,
            $jadwal->berlaku_dari,
            $jadwal->berlaku_sampai,
        );

        $validDates = [];

        foreach ($tanggalList as $tanggal) {
            $validDates[] = $tanggal;

            PresensiSesi::firstOrCreate(
                [
                    'jadwal_id' => $jadwal->id,
                    'tanggal' => $tanggal,
                ],
                [
                    'status' => 'draft',
                ]
            );
        }

        $existingSessions = PresensiSesi::query()
            ->where('jadwal_id', $jadwal->id)
            ->get();

        foreach ($existingSessions as $session) {
            $tanggal = $session->tanggal?->toDateString() ?? (string) $session->tanggal;

            if (! in_array($tanggal, $validDates, true)) {
                PresensiDetail::query()
                    ->where('presensi_sesi_id', $session->id)
                    ->delete();

                $session->delete();
            }
        }
    }

    protected function getSesiDibukaIdsByHari(string $hari)
    {
        $kelasId = Auth::user()?->wali_kelas_id;

        return PresensiSesi::query()
            ->whereHas('jadwal', function ($query) use ($kelasId, $hari) {
                $query->where('kelas_id', $kelasId)
                    ->where('hari', $hari);
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

        return PresensiDetail::query()
            ->where('siswa_id', $this->siswaRecord->id)
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

    protected function generateTanggalSesi(string $hari, $mulai, $sampai): array
    {
        $hasil = [];

        $mapHari = [
            'senin' => Carbon::MONDAY,
            'selasa' => Carbon::TUESDAY,
            'rabu' => Carbon::WEDNESDAY,
            'kamis' => Carbon::THURSDAY,
            'jumat' => Carbon::FRIDAY,
        ];

        $targetDay = $mapHari[$hari] ?? null;

        if (! $targetDay) {
            return [];
        }

        $current = Carbon::parse($mulai)->startOfDay();
        $end = Carbon::parse($sampai)->startOfDay();

        while ($current->dayOfWeek !== $targetDay) {
            $current->addDay();

            if ($current->gt($end)) {
                return [];
            }
        }

        while ($current->lte($end)) {
            $hasil[] = $current->toDateString();
            $current->addWeek();
        }

        return $hasil;
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