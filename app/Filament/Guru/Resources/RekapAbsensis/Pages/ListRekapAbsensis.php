<?php

namespace App\Filament\Guru\Resources\RekapAbsensis\Pages;

use App\Filament\Guru\Resources\RekapAbsensis\RekapAbsensiResource;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ListRekapAbsensis extends ListRecords
{
    protected static string $resource = RekapAbsensiResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->sinkronkanSemuaPresensiSesiGuru();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getTableQuery(): Builder
    {
        return Jadwal::query()
            ->with(['kelas', 'mapel'])
            ->where('guru_id', Auth::id())
            ->where('aktif', true)
            ->whereNotNull('kelas_id')
            ->whereNotNull('mapel_id')
            ->orderByRaw("
                CASE (
                    SELECT tingkat
                    FROM kelas
                    WHERE kelas.id = jadwals.kelas_id
                    LIMIT 1
                )
                    WHEN 'X' THEN 1
                    WHEN 'XI' THEN 2
                    WHEN 'XII' THEN 3
                    ELSE 99
                END
            ")
            ->orderBy(
                Kelas::query()
                    ->select('jurusan')
                    ->whereColumn('kelas.id', 'jadwals.kelas_id')
                    ->limit(1)
            )
            ->orderBy(
                Kelas::query()
                    ->select('nomor')
                    ->whereColumn('kelas.id', 'jadwals.kelas_id')
                    ->limit(1)
            )
            ->orderBy(
                Mapel::query()
                    ->select('nama')
                    ->whereColumn('mapels.id', 'jadwals.mapel_id')
                    ->limit(1)
            );
    }

    protected function sinkronkanSemuaPresensiSesiGuru(): void
    {
        $jadwals = Jadwal::query()
            ->where('guru_id', Auth::id())
            ->where('aktif', true)
            ->get();

        foreach ($jadwals as $jadwal) {
            $this->sinkronkanPresensiSesiPerJadwal($jadwal);
        }
    }

    protected function sinkronkanPresensiSesiPerJadwal(Jadwal $jadwal): void
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