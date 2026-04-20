<?php

namespace App\Filament\Guru\Resources\PresensiSesis\Pages;

use App\Filament\Guru\Resources\PresensiSesis\PresensiSesiResource;
use App\Models\Jadwal;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ViewPresensiKelas extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = PresensiSesiResource::class;

    protected string $view = 'resources.pages.page';

    public ?int $jadwal = null;

    public ?Jadwal $jadwalRecord = null;

    public function mount($jadwal): void
    {
        $this->jadwal = (int) $jadwal;

        $this->jadwalRecord = Jadwal::query()
            ->with(['kelas', 'mapel'])
            ->where('guru_id', Auth::id())
            ->where('aktif', true)
            ->findOrFail($this->jadwal);

        $this->syncPresensiSesi();
    }

    public function getTitle(): string
    {
        $kelas = $this->jadwalRecord?->kelas?->nama ?? '-';
        $mapel = $this->jadwalRecord?->mapel?->nama ?? '-';
        $hari = $this->formatHari($this->jadwalRecord?->hari);

        return "Presensi - {$hari} - {$kelas} - {$mapel}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(PresensiSesiResource::getUrl('hari', [
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

                Tables\Columns\TextColumn::make('jadwal.jam_ke')
                    ->label('Jam Ke')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jadwal.mapel.nama')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'draft' => 'Belum Dibuka',
                        'open' => 'Sedang Dibuka',
                        'closed' => 'Sudah Ditutup',
                        default => '-',
                    }),
            ])
            ->recordActions([
                Action::make('bukaPresensi')
                    ->label('Buka Absensi')
                    ->requiresConfirmation()
                    ->visible(fn (PresensiSesi $record) => $record->status === 'draft')
                    ->action(function (PresensiSesi $record): void {
                        if ($record->status === 'closed') {
                            Notification::make()
                                ->title('Sesi sudah ditutup')
                                ->warning()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => 'open',
                            'dibuka_pada' => now(),
                            'dibuka_oleh' => Auth::id(),
                        ]);

                        $siswaIds = User::query()
                            ->where('role', 'siswa')
                            ->where('kelas_id', $record->jadwal->kelas_id)
                            ->pluck('id');

                        foreach ($siswaIds as $siswaId) {
                            PresensiDetail::firstOrCreate(
                                [
                                    'presensi_sesi_id' => $record->id,
                                    'siswa_id' => $siswaId,
                                ],
                                [
                                    'status' => 'alfa',
                                    'metode' => 'guru',
                                    'waktu_isi' => null,
                                ]
                            );
                        }

                        Notification::make()
                            ->title('Presensi berhasil dibuka')
                            ->success()
                            ->send();
                    }),

                Action::make('tutupPresensi')
                    ->label('Tutup Absensi')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (PresensiSesi $record) => $record->status === 'open')
                    ->action(function (PresensiSesi $record): void {
                        if ($record->status === 'closed') {
                            Notification::make()
                                ->title('Presensi sudah ditutup')
                                ->warning()
                                ->send();

                            return;
                        }

                        $waktuTutup = now();

                        PresensiDetail::query()
                            ->where('presensi_sesi_id', $record->id)
                            ->whereNull('waktu_isi')
                            ->update([
                                'status' => 'alfa',
                                'metode' => 'guru',
                                'waktu_isi' => $waktuTutup,
                            ]);

                        $record->update([
                            'status' => 'closed',
                            'ditutup_pada' => $waktuTutup,
                            'ditutup_oleh' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Presensi berhasil ditutup')
                            ->success()
                            ->send();
                    }),

                Action::make('sudahDitutup')
                    ->label('Sudah Ditutup')
                    ->color('gray')
                    ->disabled()
                    ->visible(fn (PresensiSesi $record) => $record->status === 'closed'),

                Action::make('detail')
                    ->label('Lihat Detail')
                    ->url(fn (PresensiSesi $record) => PresensiSesiResource::getUrl('detail', [
                        'record' => $record->id,
                    ])),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return PresensiSesi::query()
            ->with(['jadwal.mapel', 'jadwal.kelas'])
            ->where('jadwal_id', $this->jadwalRecord->id)
            ->orderBy('tanggal')
            ->orderBy(
                Jadwal::query()
                    ->select('jam_ke')
                    ->whereColumn('jadwals.id', 'presensi_sesis.jadwal_id')
                    ->limit(1)
            );
    }

    protected function syncPresensiSesi(): void
    {
        if (! $this->jadwalRecord?->berlaku_dari || ! $this->jadwalRecord?->berlaku_sampai) {
            return;
        }

        $tanggalList = $this->generateTanggalSesi(
            $this->jadwalRecord->hari,
            $this->jadwalRecord->berlaku_dari,
            $this->jadwalRecord->berlaku_sampai,
        );

        $validKeys = [];

        foreach ($tanggalList as $tanggal) {
            $validKeys[] = $this->makeSessionKey($this->jadwalRecord->id, $tanggal);

            PresensiSesi::firstOrCreate(
                [
                    'jadwal_id' => $this->jadwalRecord->id,
                    'tanggal' => $tanggal,
                ],
                [
                    'status' => 'draft',
                ]
            );
        }

        $existingSessions = PresensiSesi::query()
            ->where('jadwal_id', $this->jadwalRecord->id)
            ->get();

        foreach ($existingSessions as $session) {
            $key = $this->makeSessionKey($session->jadwal_id, Carbon::parse($session->tanggal)->toDateString());

            if (! in_array($key, $validKeys, true)) {
                PresensiDetail::query()
                    ->where('presensi_sesi_id', $session->id)
                    ->delete();

                $session->delete();
            }
        }
    }

    protected function makeSessionKey(int $jadwalId, string $tanggal): string
    {
        return $jadwalId . '|' . $tanggal;
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