<?php

namespace App\Filament\Siswa\Resources\RekapAbsensis\Pages;

use App\Filament\Siswa\Resources\RekapAbsensis\RekapAbsensiResource;
use App\Models\Jadwal;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ViewRekapAbsensiHari extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = RekapAbsensiResource::class;

    protected string $view = 'resources.pages.page';

    public ?string $hari = null;

    public function mount(string $hari): void
    {
        $this->hari = strtolower($hari);

        abort_if(! Auth::check() || Auth::user()->role !== 'siswa', 403);
        abort_if(! in_array($this->hari, ['senin', 'selasa', 'rabu', 'kamis', 'jumat'], true), 404);

        $this->syncSesiByHari();
    }

    public function getTitle(): string
    {
        return 'Rekap Absensi - ' . $this->formatHari($this->hari);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(RekapAbsensiResource::getUrl('index')),
        ];
    }

    public function table(Table $table): Table
    {
        $rows = Jadwal::query()
            ->with(['mapel', 'guru'])
            ->where('kelas_id', Auth::user()->kelas_id)
            ->where('hari', $this->hari)
            ->where('aktif', true)
            ->orderBy('jam_ke')
            ->get()
            ->map(function (Jadwal $jadwal) {
                $sesiDibukaIds = PresensiSesi::query()
                    ->where('jadwal_id', $jadwal->id)
                    ->whereIn('status', ['open', 'closed'])
                    ->pluck('id');

                $details = PresensiDetail::query()
                    ->where('siswa_id', Auth::id())
                    ->whereIn('presensi_sesi_id', $sesiDibukaIds)
                    ->get();

                $hadir = $details->where('status', 'hadir')->count();
                $izin = $details->where('status', 'izin')->count();
                $sakit = $details->where('status', 'sakit')->count();
                $alfa = $details->where('status', 'alfa')->count();

                $totalSesi = $sesiDibukaIds->count();

                $persentaseHadir = $totalSesi > 0
                    ? round(($hadir / $totalSesi) * 100, 2)
                    : 0;

                return [
                    'id' => $jadwal->id,
                    'jam_ke' => $jadwal->jam_ke,
                    'mapel' => $jadwal->mapel?->nama ?? '-',
                    'guru' => $jadwal->guru?->name ?? '-',
                    'total_sesi' => $totalSesi,
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
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('jam_ke')
                    ->label('Jam Ke')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => 'Jam ke-' . $state),

                Tables\Columns\TextColumn::make('mapel')
                    ->label('Mata Pelajaran')
                    ->searchable(),

                Tables\Columns\TextColumn::make('guru')
                    ->label('Guru')
                    ->searchable(),

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
                    ->badge()
                    ->alignCenter(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    protected function syncSesiByHari(): void
    {
        $jadwals = Jadwal::query()
            ->where('kelas_id', Auth::user()->kelas_id)
            ->where('hari', $this->hari)
            ->where('aktif', true)
            ->orderBy('jam_ke')
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

            $sesi = PresensiSesi::firstOrCreate(
                [
                    'jadwal_id' => $jadwal->id,
                    'tanggal' => $tanggal,
                ],
                [
                    'status' => 'draft',
                ]
            );

            PresensiDetail::firstOrCreate(
                [
                    'presensi_sesi_id' => $sesi->id,
                    'siswa_id' => Auth::id(),
                ],
                [
                    'status' => 'alfa',
                    'metode' => 'guru',
                    'waktu_isi' => null,
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
                    ->where('siswa_id', Auth::id())
                    ->delete();
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