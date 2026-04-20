<?php

namespace App\Filament\Siswa\Widgets;

use App\Models\PresensiDetail;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SiswaStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $siswaId = $user?->id;

        $kelas = $user?->kelas?->nama ?? '-';

        $presensiHariIni = PresensiDetail::query()
            ->where('siswa_id', $siswaId)
            ->whereHas('sesi', function ($query) {
                $query->whereDate('tanggal', today());
            })
            ->count();

        $totalHadir = PresensiDetail::query()
            ->where('siswa_id', $siswaId)
            ->where('status', 'hadir')
            ->count();

        $totalAlfa = PresensiDetail::query()
            ->where('siswa_id', $siswaId)
            ->where('status', 'alfa')
            ->whereHas('sesi', function ($query) {
                $query->where('status', 'closed');
            })
            ->count();

        return [
            Stat::make('Kelas', $kelas)
                ->description('Rombel siswa')
                ->icon('heroicon-o-building-office-2'),

            Stat::make('Presensi Tercatat Hari Ini', number_format($presensiHariIni))
                ->description('Jumlah presensi yang sudah masuk hari ini')
                ->icon('heroicon-o-clipboard-document-check'),

            Stat::make('Total Hadir', number_format($totalHadir))
                ->description('Jumlah kehadiran')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Total Alfa', number_format($totalAlfa))
                ->description('Jumlah alfa final')
                ->icon('heroicon-o-x-circle'),
        ];
    }
}