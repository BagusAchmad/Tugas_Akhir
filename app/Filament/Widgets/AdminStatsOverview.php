<?php

namespace App\Filament\Widgets;

use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalGuru = User::query()
            ->where('role', 'guru')
            ->count();

        $totalSiswa = User::query()
            ->where('role', 'siswa')
            ->count();

        $totalRombelAktif = Kelas::query()
            ->where('aktif', true)
            ->count();

        $totalMapelAktif = Mapel::query()
            ->where('aktif', true)
            ->count();

        return [
            Stat::make('Akun Guru', number_format($totalGuru))
                ->description('Total akun guru')
                ->icon('heroicon-o-academic-cap'),

            Stat::make('Akun Siswa', number_format($totalSiswa))
                ->description('Total akun siswa')
                ->icon('heroicon-o-users'),

            Stat::make('Rombel Aktif', number_format($totalRombelAktif))
                ->description('Kelas yang aktif')
                ->icon('heroicon-o-building-office-2'),

            Stat::make('Mata Pelajaran Aktif', number_format($totalMapelAktif))
                ->description('Mapel yang aktif')
                ->icon('heroicon-o-book-open'),
        ];
    }
}