<?php

namespace App\Filament\Resources\RekapAbsensis\Pages;

use App\Filament\Resources\RekapAbsensis\RekapAbsensiResource;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ViewRekapAbsensiHari extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = RekapAbsensiResource::class;

    protected string $view = 'resources.pages.page';

    public ?int $record = null;

    public ?Kelas $kelas = null;

    public ?string $hari = null;

    public function mount($record, string $hari): void
    {
        $this->record = (int) $record;
        $this->hari = strtolower($hari);

        abort_if(! in_array($this->hari, ['senin', 'selasa', 'rabu', 'kamis', 'jumat'], true), 404);

        $this->kelas = Kelas::findOrFail($this->record);

        $this->syncSesiByHari();
    }

    public function getTitle(): string
    {
        return 'Rekap Absensi ' . $this->formatHari($this->hari) . ' - ' . ($this->kelas->nama ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(RekapAbsensiResource::getUrl('view', [
                    'record' => $this->kelas,
                ])),
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
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('jam_ke')
                    ->label('Jam Ke')
                    ->formatStateUsing(fn ($state) => 'Jam ke-' . $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('mapel.nama')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('guru.name')
                    ->label('Guru')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('wali_kelas')
                    ->label('Wali Kelas')
                    ->state(function (): string {
                        return \App\Models\User::query()
                            ->where('role', 'guru')
                            ->where('wali_kelas_id', $this->kelas?->id)
                            ->value('name') ?? '-';
                    }),

                Tables\Columns\IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('total_pertemuan')
                    ->label('Total Pertemuan')
                    ->state(fn (Jadwal $record): int => $this->hitungTotalPertemuan($record))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sudah_dibuka')
                    ->label('Sudah Dibuka')
                    ->state(fn (Jadwal $record): int => $this->hitungSudahDibuka($record))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->state(function (Jadwal $record): string {
                        $sudahDibuka = $this->hitungSudahDibuka($record);
                        $totalPertemuan = $this->hitungTotalPertemuan($record);

                        return $sudahDibuka . ' / ' . $totalPertemuan;
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('hadir')
                    ->label('Hadir')
                    ->state(fn (Jadwal $record): int => $this->hitungStatusByJadwal($record, 'hadir'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('izin')
                    ->label('Izin')
                    ->state(fn (Jadwal $record): int => $this->hitungStatusByJadwal($record, 'izin'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sakit')
                    ->label('Sakit')
                    ->state(fn (Jadwal $record): int => $this->hitungStatusByJadwal($record, 'sakit'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('alfa')
                    ->label('Alfa')
                    ->state(fn (Jadwal $record): int => $this->hitungStatusByJadwal($record, 'alfa'))
                    ->alignCenter(),
            ])
            ->recordActions([
                Action::make('lihatPertemuan')
                    ->label('Lihat Pertemuan')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Jadwal $record) => RekapAbsensiResource::getUrl('pelajaran', [
                        'record' => $this->kelas,
                        'jadwal' => $record->id,
                    ])),
            ])
            ->toolbarActions([])
            ->paginated(false);
    }

    protected function getTableQuery(): Builder
    {
        return Jadwal::query()
            ->with(['mapel', 'guru'])
            ->where('kelas_id', $this->kelas->id)
            ->where('hari', $this->hari)
            ->orderBy('jam_ke')
            ->orderBy('id');
    }

    protected function syncSesiByHari(): void
    {
        $jadwals = Jadwal::query()
            ->where('kelas_id', $this->kelas->id)
            ->where('hari', $this->hari)
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

    protected function hitungTotalPertemuan(Jadwal $jadwal): int
    {
        if (! $jadwal->berlaku_dari || ! $jadwal->berlaku_sampai) {
            return 0;
        }

        return count($this->generateTanggalSesi(
            $jadwal->hari,
            $jadwal->berlaku_dari,
            $jadwal->berlaku_sampai,
        ));
    }

    protected function hitungSudahDibuka(Jadwal $jadwal): int
    {
        return PresensiSesi::query()
            ->where('jadwal_id', $jadwal->id)
            ->whereIn('status', ['open', 'closed'])
            ->count();
    }

    protected function hitungStatusByJadwal(Jadwal $jadwal, string $status): int
    {
        $sesiIds = PresensiSesi::query()
            ->where('jadwal_id', $jadwal->id)
            ->whereIn('status', ['open', 'closed'])
            ->pluck('id');

        if ($sesiIds->isEmpty()) {
            return 0;
        }

        return PresensiDetail::query()
            ->whereIn('presensi_sesi_id', $sesiIds)
            ->where('status', $status)
            ->count();
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