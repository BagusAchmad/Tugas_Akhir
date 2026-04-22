<?php

namespace App\Filament\Guru\Pages;

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
use Illuminate\Support\Facades\Auth;

class RekapWaliKelasSiswa extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected string $view = 'resources.pages.page';

    protected static ?string $slug = 'rekap-wali-kelas-siswa/{record}';

    public ?int $record = null;

    public ?PresensiSesi $sesi = null;

    public function mount($record): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'guru', 403);
        abort_unless(filled(Auth::user()?->wali_kelas_id), 403);

        $this->record = (int) $record;

        $this->sesi = PresensiSesi::query()
            ->with(['jadwal.kelas', 'jadwal.mapel', 'jadwal.guru'])
            ->findOrFail($this->record);

        abort_unless(
            (int) $this->sesi->jadwal->kelas_id === (int) Auth::user()->wali_kelas_id,
            403
        );

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

        return "Detail Siswa - {$kelas} - {$mapel} - Jam {$jamKe} - {$tanggal}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(fn () => RekapWaliKelasPertemuan::getUrl([
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
            ->recordActions([])
            ->toolbarActions([])
            ->recordUrl(null);
    }

    protected function syncDetailSiswa(): void
    {
        $kelasId = $this->sesi?->jadwal?->kelas_id;

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
            ->whereNotIn('siswa_id', $siswaIds)
            ->delete();
    }
}