<?php

namespace App\Filament\Guru\Resources\PresensiSesis\Pages;

use App\Filament\Guru\Resources\PresensiSesis\PresensiSesiResource;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DetailPresensiSesi extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = PresensiSesiResource::class;

    protected string $view = 'resources.pages.page';

    public ?int $record = null;

    public ?PresensiSesi $sesi = null;

    public function mount($record): void
    {
        $this->record = (int) $record;

        $this->sesi = PresensiSesi::with(['jadwal.kelas', 'jadwal.mapel'])->findOrFail($this->record);

        if ($this->sesi->jadwal->guru_id !== Auth::id()) {
            abort(403);
        }

        $this->syncDetailSiswa();
    }

    public function getTitle(): string
    {
        $tanggal = $this->sesi?->tanggal
            ? Carbon::parse($this->sesi->tanggal)->format('d/m/Y')
            : '-';

        $kelas = $this->sesi?->jadwal?->kelas?->nama ?? '-';
        $mapel = $this->sesi?->jadwal?->mapel?->nama ?? '-';
        $jamKe = $this->sesi?->jadwal?->jam_ke ?? '-';

        return "Detail Presensi - {$kelas} - {$mapel} - Jam {$jamKe} - {$tanggal}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(fn () => PresensiSesiResource::getUrl('view', [
                    'jadwal' => $this->sesi?->jadwal_id,
                ])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PresensiDetail::query()
                    ->where('presensi_sesi_id', $this->record)
                    ->whereHas('siswa', function ($query) {
                        $query
                            ->where('role', 'siswa')
                            ->where('kelas_id', $this->sesi?->jadwal?->kelas_id);
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
                    ->alignCenter()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'hadir' => 'Hadir',
                            'izin' => 'Izin',
                            'sakit' => 'Sakit',
                            'alfa' => ($this->sesi?->status === 'closed' ? 'Alfa' : 'Belum Absen'),
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
            ->recordActions([
                Action::make('ubahStatus')
                    ->label('Ubah Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'hadir' => 'Hadir',
                                'izin' => 'Izin',
                                'sakit' => 'Sakit',
                                'alfa' => 'Alfa',
                            ])
                            ->placeholder('Pilih salah satu opsi')
                            ->native(false)
                            ->required(),
                    ])
                    ->fillForm(function (PresensiDetail $record) {
                        return [
                            'status' => $record->status,
                        ];
                    })
                    ->action(function (PresensiDetail $record, array $data) {
                        abort_unless($record->presensi_sesi_id === $this->record, 403);

                        $record->update([
                            'status' => $data['status'],
                            'metode' => 'guru',
                            'waktu_isi' => now(),
                        ]);

                        Notification::make()
                            ->title('Status kehadiran siswa berhasil diperbarui')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordUrl(null);
    }

    protected function syncDetailSiswa(): void
    {
        $kelasId = $this->sesi?->jadwal?->kelas_id;

        if (! $kelasId) {
            return;
        }

        $siswaIdsAktif = User::query()
            ->where('role', 'siswa')
            ->where('kelas_id', $kelasId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($siswaIdsAktif as $siswaId) {
            PresensiDetail::firstOrCreate(
                [
                    'presensi_sesi_id' => $this->sesi->id,
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
            ->where('presensi_sesi_id', $this->sesi->id)
            ->whereNotIn('siswa_id', $siswaIdsAktif)
            ->delete();
    }
}