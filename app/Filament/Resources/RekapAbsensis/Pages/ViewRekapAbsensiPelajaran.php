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

class ViewRekapAbsensiPelajaran extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = RekapAbsensiResource::class;

    protected string $view = 'resources.pages.page';

    public ?int $record = null;
    public ?int $jadwal = null;

    public ?Kelas $kelas = null;
    public ?Jadwal $jadwalRecord = null;

    public function mount($record, $jadwal): void
    {
        $this->record = (int) $record;
        $this->jadwal = (int) $jadwal;

        $this->kelas = Kelas::findOrFail($this->record);

        $this->jadwalRecord = Jadwal::query()
            ->with(['kelas', 'mapel', 'guru'])
            ->where('kelas_id', $this->kelas->id)
            ->findOrFail($this->jadwal);

        $this->syncPresensiSesi($this->jadwalRecord);
    }

    public function getTitle(): string
    {
        $hari = $this->formatHari($this->jadwalRecord?->hari);
        $kelas = $this->jadwalRecord?->kelas?->nama ?? '-';
        $mapel = $this->jadwalRecord?->mapel?->nama ?? '-';

        return "Pertemuan - {$hari} - {$kelas} - {$mapel}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(RekapAbsensiResource::getUrl('hari', [
                    'record' => $this->kelas,
                    'hari' => $this->jadwalRecord?->hari,
                ])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('tanggal', 'asc')
            ->recordAction(null)
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hari_tampil')
                    ->label('Hari')
                    ->state(fn (PresensiSesi $record) => $this->formatHariDariTanggal($record->tanggal)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'draft' => 'Belum Dibuka',
                        'open' => 'Sedang Dibuka',
                        'closed' => 'Sudah Ditutup',
                        default => '-',
                    }),

                Tables\Columns\TextColumn::make('hadir')
                    ->label('Hadir')
                    ->state(fn (PresensiSesi $record): int => $this->hitungStatus($record, 'hadir'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('izin')
                    ->label('Izin')
                    ->state(fn (PresensiSesi $record): int => $this->hitungStatus($record, 'izin'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sakit')
                    ->label('Sakit')
                    ->state(fn (PresensiSesi $record): int => $this->hitungStatus($record, 'sakit'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('alfa')
                    ->label('Alfa')
                    ->state(fn (PresensiSesi $record): int => $this->hitungStatus($record, 'alfa'))
                    ->alignCenter(),
            ])
            ->recordActions([
                Action::make('lihatSiswa')
                    ->label('Lihat Siswa')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PresensiSesi $record) => RekapAbsensiResource::getUrl('siswa', [
                        'record' => $this->kelas,
                        'sesi' => $record->id,
                    ])),
            ])
            ->toolbarActions([]);
    }

    protected function getTableQuery(): Builder
    {
        return PresensiSesi::query()
            ->with(['jadwal.mapel', 'jadwal.kelas'])
            ->where('jadwal_id', $this->jadwalRecord->id)
            ->orderBy('tanggal');
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

    protected function hitungStatus(PresensiSesi $sesi, string $status): int
    {
        if ($sesi->status === 'draft') {
            return 0;
        }

        if ($sesi->status === 'open' && $status === 'alfa') {
            return 0;
        }

        return PresensiDetail::query()
            ->where('presensi_sesi_id', $sesi->id)
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

    protected function formatHariDariTanggal($tanggal): string
    {
        return match (Carbon::parse($tanggal)->dayOfWeek) {
            Carbon::MONDAY => 'Senin',
            Carbon::TUESDAY => 'Selasa',
            Carbon::WEDNESDAY => 'Rabu',
            Carbon::THURSDAY => 'Kamis',
            Carbon::FRIDAY => 'Jumat',
            Carbon::SATURDAY => 'Sabtu',
            Carbon::SUNDAY => 'Minggu',
            default => '-',
        };
    }
}