<?php

namespace App\Filament\Siswa\Resources\PresensiDetails\Pages;

use App\Filament\Siswa\Resources\PresensiDetails\PresensiDetailResource;
use App\Models\Jadwal;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ViewPresensiHari extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = PresensiDetailResource::class;

    protected string $view = 'resources.pages.page';

    public ?string $hari = null;

    public ?User $siswa = null;

    public function mount(string $hari): void
    {
        $this->hari = strtolower($hari);
        $this->siswa = Auth::user();

        abort_if(! $this->siswa || $this->siswa->role !== 'siswa', 403);
        abort_if(! in_array($this->hari, ['senin', 'selasa', 'rabu', 'kamis', 'jumat']), 404);

        $this->syncPresensiHari();
    }

    public function getTitle(): string
    {
        return 'Presensi - ' . ucfirst($this->hari ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(PresensiDetailResource::getUrl('index')),
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
                Tables\Columns\TextColumn::make('nomor')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jadwal.jam_ke')
                    ->label('Jam')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => 'Jam ke-' . $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('jadwal.mapel.nama')
                    ->label('Mapel')
                    ->searchable(),

                Tables\Columns\TextColumn::make('jadwal.guru.name')
                    ->label('Guru')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Sesi')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'Belum Dibuka',
                        'open' => 'Sedang Dibuka',
                        'closed' => 'Ditutup',
                        default => (string) $state,
                    }),

                Tables\Columns\TextColumn::make('presensi_kamu')
                    ->label('Presensi Kamu')
                    ->badge()
                    ->alignCenter()
                    ->state(function (PresensiSesi $record) {
                        $detail = $record->details->firstWhere('siswa_id', Auth::id());

                        return match ($detail?->status) {
                            'hadir' => 'Hadir',
                            'izin' => 'Izin',
                            'sakit' => 'Sakit',
                            'alfa', null => ($record->status === 'closed' ? 'Alfa' : 'Belum Absen'),
                            default => (string) $detail?->status,
                        };
                    }),

                Tables\Columns\TextColumn::make('diisi_pada')
                    ->label('Diisi Pada')
                    ->alignCenter()
                    ->state(function (PresensiSesi $record) {
                        $detail = $record->details->firstWhere('siswa_id', Auth::id());

                        if (! $detail?->waktu_isi) {
                            return '-';
                        }

                        return Carbon::parse($detail->waktu_isi)
                            ->timezone(config('app.timezone', 'Asia/Jakarta'))
                            ->format('d M Y H:i');
                    }),
            ])
            ->recordActions([
                Action::make('isi_presensi')
                    ->label('Isi Presensi')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(function (PresensiSesi $record) {
                        return $record->status === 'open'
                            && $record->details->firstWhere('siswa_id', Auth::id());
                    })
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'hadir' => 'Hadir',
                                'izin' => 'Izin',
                                'sakit' => 'Sakit',
                            ])
                            ->placeholder('Select option')
                            ->default(null)
                            ->native(false)
                            ->required(),
                    ])
                    ->fillForm(function (PresensiSesi $record) {
                        $detail = $record->details->firstWhere('siswa_id', Auth::id());

                        return [
                            'status' => $detail && $detail->waktu_isi && in_array($detail->status, ['hadir', 'izin', 'sakit'], true)
                                ? $detail->status
                                : null,
                        ];
                    })
                    ->action(function (PresensiSesi $record, array $data) {
                        abort_unless($record->status === 'open', 403);

                        $detail = PresensiDetail::query()
                            ->where('presensi_sesi_id', $record->id)
                            ->where('siswa_id', Auth::id())
                            ->firstOrFail();

                        $detail->update([
                            'status' => $data['status'],
                            'metode' => 'siswa',
                            'waktu_isi' => now(),
                        ]);

                        Notification::make()
                            ->title('Presensi berhasil diisi')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Isi Presensi')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal'),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return PresensiSesi::query()
            ->with([
                'jadwal.mapel',
                'jadwal.guru',
                'details' => fn ($query) => $query->where('siswa_id', Auth::id()),
            ])
            ->whereHas('jadwal', function (Builder $query) {
                $query
                    ->where('kelas_id', $this->siswa->kelas_id)
                    ->where('hari', $this->hari)
                    ->where('aktif', true);
            })
            ->orderBy('tanggal')
            ->orderBy(
                Jadwal::query()
                    ->select('jam_ke')
                    ->whereColumn('jadwals.id', 'presensi_sesis.jadwal_id')
                    ->limit(1)
            );
    }

    protected function syncPresensiHari(): void
    {
        $jadwals = Jadwal::query()
            ->where('kelas_id', $this->siswa->kelas_id)
            ->where('hari', $this->hari)
            ->where('aktif', true)
            ->orderBy('jam_ke')
            ->get();

        foreach ($jadwals as $jadwal) {
            if (! $jadwal->berlaku_dari || ! $jadwal->berlaku_sampai) {
                continue;
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
                        'siswa_id' => $this->siswa->id,
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
                        ->where('siswa_id', $this->siswa->id)
                        ->delete();
                }
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