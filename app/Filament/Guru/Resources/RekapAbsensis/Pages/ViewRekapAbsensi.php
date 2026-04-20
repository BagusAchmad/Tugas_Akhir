<?php

namespace App\Filament\Guru\Resources\RekapAbsensis\Pages;

use App\Filament\Guru\Resources\RekapAbsensis\RekapAbsensiResource;
use App\Models\Jadwal;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ViewRekapAbsensi extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = RekapAbsensiResource::class;

    protected string $view = 'resources.pages.page';

    public ?int $record = null;

    public ?Jadwal $jadwal = null;

    public function mount($record): void
    {
        $this->record = (int) $record;

        $this->jadwal = Jadwal::query()
            ->with(['kelas', 'mapel'])
            ->where('guru_id', Auth::id())
            ->where('aktif', true)
            ->findOrFail($this->record);

        $this->sinkronkanPresensiSesi();
    }

    public function getTitle(): string
    {
        $kelas = $this->jadwal?->kelas?->nama ?? '-';
        $mapel = $this->jadwal?->mapel?->nama ?? '-';

        return "Rekap Absensi - {$kelas} - {$mapel}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->url(RekapAbsensiResource::getUrl('index')),
        ];
    }

    public function getViewData(): array
    {
        $totalPertemuan = PresensiSesi::query()
            ->where('jadwal_id', $this->jadwal->id)
            ->count();

        $sudahDibuka = PresensiSesi::query()
            ->where('jadwal_id', $this->jadwal->id)
            ->whereIn('status', ['open', 'closed'])
            ->count();

        return [
            'kelasNama' => $this->jadwal?->kelas?->nama ?? '-',
            'mapelNama' => $this->jadwal?->mapel?->nama ?? '-',
            'totalPertemuan' => $totalPertemuan,
            'sudahDibuka' => $sudahDibuka,
            'progressPertemuan' => $sudahDibuka . ' / ' . $totalPertemuan,
        ];
    }

    public function table(Table $table): Table
    {
        $sudahDibuka = PresensiSesi::query()
            ->where('jadwal_id', $this->jadwal->id)
            ->whereIn('status', ['open', 'closed'])
            ->pluck('id');

        $rows = User::query()
            ->where('role', 'siswa')
            ->where('kelas_id', $this->jadwal->kelas_id)
            ->orderBy('name')
            ->get()
            ->map(function (User $siswa) use ($sudahDibuka) {
                $details = PresensiDetail::query()
                    ->where('siswa_id', $siswa->id)
                    ->whereIn('presensi_sesi_id', $sudahDibuka)
                    ->get();

                $hadir = $details->where('status', 'hadir')->count();
                $izin = $details->where('status', 'izin')->count();
                $sakit = $details->where('status', 'sakit')->count();
                $alfa = $details->where('status', 'alfa')->count();

                $totalDibuka = $sudahDibuka->count();

                $persentase = $totalDibuka > 0
                    ? round(($hadir / $totalDibuka) * 100, 2)
                    : 0;

                return [
                    'id' => $siswa->id,
                    'nama' => $siswa->name,
                    'nis' => $siswa->nis,
                    'hadir' => $hadir,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'alfa' => $alfa,
                    'persentase_hadir' => $persentase . '%',
                ];
            })
            ->values();

        return $table
            ->records(fn (): Collection => $rows)
            ->recordAction(null)
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Siswa')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable(),

                Tables\Columns\TextColumn::make('hadir')
                    ->label('Hadir'),

                Tables\Columns\TextColumn::make('izin')
                    ->label('Izin'),

                Tables\Columns\TextColumn::make('sakit')
                    ->label('Sakit'),

                Tables\Columns\TextColumn::make('alfa')
                    ->label('Alfa'),

                Tables\Columns\TextColumn::make('persentase_hadir')
                    ->label('Persentase Hadir'),
            ]);
    }

    protected function sinkronkanPresensiSesi(): void
    {
        if (! $this->jadwal->berlaku_dari || ! $this->jadwal->berlaku_sampai) {
            return;
        }

        $tanggalList = $this->generateTanggalSesi(
            $this->jadwal->hari,
            $this->jadwal->berlaku_dari,
            $this->jadwal->berlaku_sampai,
        );

        $validDates = [];

        foreach ($tanggalList as $tanggal) {
            $validDates[] = $tanggal;

            PresensiSesi::firstOrCreate(
                [
                    'jadwal_id' => $this->jadwal->id,
                    'tanggal' => $tanggal,
                ],
                [
                    'status' => 'draft',
                ]
            );
        }

        $existingSessions = PresensiSesi::query()
            ->where('jadwal_id', $this->jadwal->id)
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

    protected function generateTanggalSesi(string $hari, $mulai, $sampai): array
    {
        $hasil = [];

        $mapHari = [
            'senin' => \Illuminate\Support\Carbon::MONDAY,
            'selasa' => \Illuminate\Support\Carbon::TUESDAY,
            'rabu' => \Illuminate\Support\Carbon::WEDNESDAY,
            'kamis' => \Illuminate\Support\Carbon::THURSDAY,
            'jumat' => \Illuminate\Support\Carbon::FRIDAY,
        ];

        $targetDay = $mapHari[$hari] ?? null;

        if (! $targetDay) {
            return [];
        }

        $current = \Illuminate\Support\Carbon::parse($mulai)->startOfDay();
        $end = \Illuminate\Support\Carbon::parse($sampai)->startOfDay();

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