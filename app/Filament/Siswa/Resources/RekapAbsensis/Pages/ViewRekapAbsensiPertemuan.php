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
use Illuminate\Support\Facades\Auth;

class ViewRekapAbsensiPertemuan extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = RekapAbsensiResource::class;

    protected string $view = 'resources.pages.page';

    public ?int $jadwal = null;

    public ?Jadwal $jadwalRecord = null;

    public function mount($jadwal): void
    {
        $this->jadwal = (int) $jadwal;

        abort_if(! Auth::check() || Auth::user()->role !== 'siswa', 403);

        $this->jadwalRecord = Jadwal::query()
            ->with(['kelas', 'mapel', 'guru'])
            ->where('kelas_id', Auth::user()->kelas_id)
            ->where('aktif', true)
            ->findOrFail($this->jadwal);

        $this->syncPresensiSesi($this->jadwalRecord);
    }

    public function getTitle(): string
    {
        $hari = $this->formatHari($this->jadwalRecord?->hari);
        $mapel = $this->jadwalRecord?->mapel?->nama ?? '-';
        $jamKe = $this->jadwalRecord?->jam_ke ?? '-';

        return "Detail Rekap - {$hari} - {$mapel} - Jam {$jamKe}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(RekapAbsensiResource::getUrl('hari', [
                    'hari' => $this->jadwalRecord?->hari,
                ])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PresensiSesi::query()
                    ->with([
                        'details' => fn ($query) => $query->where('siswa_id', Auth::id()),
                    ])
                    ->where('jadwal_id', $this->jadwalRecord->id)
                    ->orderBy('tanggal')
            )
            ->recordAction(null)
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

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

                Tables\Columns\TextColumn::make('diisi_oleh')
                    ->label('Diisi Oleh')
                    ->alignCenter()
                    ->state(function (PresensiSesi $record) {
                        $detail = $record->details->firstWhere('siswa_id', Auth::id());

                        if (! $detail?->waktu_isi) {
                            return '-';
                        }

                        return match ($detail?->metode) {
                            'siswa' => 'Siswa',
                            'guru' => 'Guru',
                            default => '-',
                        };
                    }),

                Tables\Columns\TextColumn::make('waktu_isi')
                    ->label('Waktu Isi')
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
            ->toolbarActions([]);
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