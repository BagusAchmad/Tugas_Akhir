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

class RekapPerSiswa extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'resources.pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 31;

    public function mount(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'guru', 403);
        abort_unless(filled(Auth::user()?->wali_kelas_id), 403);

        $this->syncSemuaJadwal();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        $kelasNama = Auth::user()?->waliKelas?->nama;

        return $kelasNama
            ? "Rekap Siswa - {$kelasNama}"
            : 'Rekap Siswa';
    }

    public function table(Table $table): Table
    {
        $kelasId = Auth::user()?->wali_kelas_id;

        $sesiDibukaIds = PresensiSesi::query()
            ->whereHas('jadwal', function ($query) use ($kelasId) {
                $query->where('kelas_id', $kelasId);
            })
            ->whereIn('status', ['open', 'closed'])
            ->pluck('id');

        $totalSesiDibuka = $sesiDibukaIds->count();

        $rows = User::query()
            ->where('role', 'siswa')
            ->where('kelas_id', $kelasId)
            ->orderBy('name')
            ->get()
            ->map(function (User $siswa) use ($sesiDibukaIds, $totalSesiDibuka) {
                $details = PresensiDetail::query()
                    ->where('siswa_id', $siswa->id)
                    ->whereIn('presensi_sesi_id', $sesiDibukaIds)
                    ->get();

                $hadir = $details->where('status', 'hadir')->count();
                $izin = $details->where('status', 'izin')->count();
                $sakit = $details->where('status', 'sakit')->count();
                $alfa = $details->where('status', 'alfa')->count();

                $persentaseHadir = $totalSesiDibuka > 0
                    ? round(($hadir / $totalSesiDibuka) * 100, 2)
                    : 0;

                return [
                    'id' => $siswa->id,
                    'nama' => $siswa->name,
                    'nis' => $siswa->nis,
                    'total_sesi' => $totalSesiDibuka,
                    'hadir' => $hadir,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'alfa' => $alfa,
                    'persentase_hadir' => $persentaseHadir . '%',
                ];
            })
            ->values();

        return $table
            ->records(fn (): Collection => $rows)
            ->recordAction(null)
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),

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
                    ->url(fn (array $record): string => RekapPerSiswaDetail::getUrl([
                        'siswa' => $record['id'],
                    ])),
            ])
            ->toolbarActions([])
            ->paginated(false);
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
}