<?php

namespace App\Filament\Resources\RekapAbsensis\Pages;

use App\Filament\Resources\RekapAbsensis\RekapAbsensiResource;
use App\Models\Kelas;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class ViewRekapAbsensiSiswa extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = RekapAbsensiResource::class;

    protected string $view = 'resources.pages.page';

    public ?int $record = null;
    public ?int $sesi = null;

    public ?Kelas $kelas = null;
    public ?PresensiSesi $sesiRecord = null;

    public function mount($record, $sesi): void
    {
        $this->record = (int) $record;
        $this->sesi = (int) $sesi;

        $this->kelas = Kelas::findOrFail($this->record);

        $this->sesiRecord = PresensiSesi::query()
            ->with(['jadwal.kelas', 'jadwal.mapel', 'jadwal.guru'])
            ->whereHas('jadwal', function ($query) {
                $query->where('kelas_id', $this->kelas->id);
            })
            ->findOrFail($this->sesi);

        $this->syncDetailSiswa();
    }

    public function getTitle(): string
    {
        $tanggal = $this->sesiRecord?->tanggal
            ? Carbon::parse($this->sesiRecord->tanggal)->format('d/m/Y')
            : '-';

        $kelas = $this->sesiRecord?->jadwal?->kelas?->nama ?? '-';
        $mapel = $this->sesiRecord?->jadwal?->mapel?->nama ?? '-';
        $jamKe = $this->sesiRecord?->jadwal?->jam_ke ?? '-';

        return "Detail Siswa - {$kelas} - {$mapel} - Jam {$jamKe} - {$tanggal}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(RekapAbsensiResource::getUrl('pelajaran', [
                    'record' => $this->kelas,
                    'jadwal' => $this->sesiRecord?->jadwal_id,
                ])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PresensiDetail::query()
                    ->where('presensi_sesi_id', $this->sesiRecord->id)
                    ->whereHas('siswa', function ($query) {
                        $query
                            ->where('role', 'siswa')
                            ->where('kelas_id', $this->kelas?->id);
                    })
                    ->with('siswa')
            )
            ->defaultSort('siswa.name')
            ->columns([
                Tables\Columns\TextColumn::make('siswa.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('siswa.nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir', 'Hadir' => 'success',
                        'izin', 'Izin', 'sakit', 'Sakit' => 'warning',
                        'alfa', 'Alfa', 'Belum Absen' => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'hadir' => 'Hadir',
                            'izin' => 'Izin',
                            'sakit' => 'Sakit',
                            'alfa' => ($this->sesiRecord?->status === 'closed' ? 'Alfa' : 'Belum Absen'),
                            default => strtoupper((string) $state),
                        };
                    }),

                Tables\Columns\TextColumn::make('waktu_isi')
                    ->label('Waktu Absen')
                    ->alignCenter()
                    ->state(function (PresensiDetail $record) {
                        if (! $record->waktu_isi) {
                            return '-';
                        }

                        return Carbon::parse($record->waktu_isi)
                            ->timezone(config('app.timezone', 'Asia/Jakarta'))
                            ->format('d/m/Y H:i');
                    }),

                Tables\Columns\TextColumn::make('metode')
                    ->label('Diisi Oleh')
                    ->alignCenter()
                    ->state(function (PresensiDetail $record) {
                        if (! $record->waktu_isi) {
                            return '-';
                        }

                        return match ($record->metode) {
                            'guru' => 'Guru',
                            'siswa' => 'Siswa',
                            default => '-',
                        };
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->recordUrl(null);
    }

    protected function syncDetailSiswa(): void
    {
        $kelasId = $this->kelas?->id;

        if (! $kelasId) {
            return;
        }

        $siswaIds = User::query()
            ->where('role', 'siswa')
            ->where('kelas_id', $kelasId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($siswaIds as $siswaId) {
            PresensiDetail::firstOrCreate(
                [
                    'presensi_sesi_id' => $this->sesiRecord->id,
                    'siswa_id' => $siswaId,
                ],
                [
                    'status' => 'alfa',
                    'metode' => 'guru',
                    'waktu_isi' => null,
                ]
            );
        }

        PresensiDetail::query()
            ->where('presensi_sesi_id', $this->sesiRecord->id)
            ->whereNotIn('siswa_id', $siswaIds)
            ->delete();
    }
}