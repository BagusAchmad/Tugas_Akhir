<?php

namespace App\Filament\Guru\Pages;

use App\Models\Jadwal;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class RekapWaliKelasDetail extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected string $view = 'resources.pages.page';

    protected static ?string $slug = 'rekap-wali-kelas-detail/{hari}';

    public ?string $hari = null;

    public function mount(string $hari): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'guru', 403);
        abort_unless(filled(Auth::user()?->wali_kelas_id), 403);

        $hari = strtolower($hari);

        abort_unless(in_array($hari, ['senin', 'selasa', 'rabu', 'kamis', 'jumat'], true), 404);

        $this->hari = $hari;

        $this->syncSesiByHari();
    }

    public function getTitle(): string
    {
        $kelasNama = Auth::user()?->waliKelas?->nama;
        $hariLabel = $this->formatHari($this->hari);

        return $kelasNama
            ? "Rekap Pelajaran - {$hariLabel} - {$kelasNama}"
            : "Rekap Pelajaran - {$hariLabel}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(RekapWaliKelas::getUrl()),
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
            ])
            ->recordActions([
                Action::make('lihatPertemuan')
                    ->label('Lihat Pertemuan')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Jadwal $record): string => RekapWaliKelasPertemuan::getUrl([
                        'jadwal' => $record->id,
                    ])),
            ])
            ->toolbarActions([])
            ->paginated(false);
    }

    protected function getTableQuery(): Builder
    {
        $kelasId = Auth::user()?->wali_kelas_id;

        return Jadwal::query()
            ->with(['kelas', 'mapel', 'guru'])
            ->where('kelas_id', $kelasId)
            ->where('hari', $this->hari)
            ->orderBy('jam_ke')
            ->orderBy('id');
    }

    protected function syncSesiByHari(): void
    {
        $kelasId = Auth::user()?->wali_kelas_id;

        $jadwals = Jadwal::query()
            ->where('kelas_id', $kelasId)
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