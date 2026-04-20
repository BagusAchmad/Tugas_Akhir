<?php

namespace App\Filament\Guru\Widgets;

use App\Models\Jadwal;
use App\Models\PresensiSesi;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class GuruStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $guruId = Auth::id();
        $hariIni = $this->getHariIni();

        $jadwalHariIni = Jadwal::query()
            ->where('guru_id', $guruId)
            ->where('hari', $hariIni)
            ->where('aktif', true)
            ->count();

        $sesiPresensiHariIni = PresensiSesi::query()
            ->whereDate('tanggal', today())
            ->whereHas('jadwal', function ($query) use ($guruId) {
                $query->where('guru_id', $guruId);
            })
            ->count();

        $mapelDiampu = Jadwal::query()
            ->where('guru_id', $guruId)
            ->where('aktif', true)
            ->distinct('mapel_id')
            ->count('mapel_id');

        $rombelDiampu = Jadwal::query()
            ->where('guru_id', $guruId)
            ->where('aktif', true)
            ->distinct('kelas_id')
            ->count('kelas_id');

        return [
            Stat::make('Jadwal Hari Ini', number_format($jadwalHariIni))
                ->description('Jadwal aktif hari ini')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Sesi Presensi Hari Ini', number_format($sesiPresensiHariIni))
                ->description('Sesi presensi tercatat hari ini')
                ->icon('heroicon-o-clipboard-document-check'),

            Stat::make('Mapel Diampu', number_format($mapelDiampu))
                ->description('Mata pelajaran yang diampu')
                ->icon('heroicon-o-book-open'),

            Stat::make('Rombel Diampu', number_format($rombelDiampu))
                ->description('Rombel yang diajar')
                ->icon('heroicon-o-building-office-2'),
        ];
    }

    protected function getHariIni(): string
    {
        $hariMap = [
            'Monday' => 'senin',
            'Tuesday' => 'selasa',
            'Wednesday' => 'rabu',
            'Thursday' => 'kamis',
            'Friday' => 'jumat',
            'Saturday' => 'sabtu',
            'Sunday' => 'minggu',
        ];

        return $hariMap[now()->format('l')] ?? strtolower(now()->format('l'));
    }
}