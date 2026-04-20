<?php

namespace App\Filament\Widgets;

use App\Models\PresensiDetail;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminKehadiranHariIni extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Ringkasan Kehadiran Hari Ini';
    }

    protected function getStats(): array
    {
        $hariIni = Carbon::today();

        $hadir = $this->getTotalStatus($hariIni, 'hadir');
        $izin = $this->getTotalStatus($hariIni, 'izin');
        $sakit = $this->getTotalStatus($hariIni, 'sakit');
        $alfa = $this->getTotalStatus($hariIni, 'alfa');

        return [
            Stat::make('Hadir', number_format($hadir))
                ->icon('heroicon-o-check-circle'),

            Stat::make('Izin', number_format($izin))
                ->icon('heroicon-o-document-text'),

            Stat::make('Sakit', number_format($sakit))
                ->icon('heroicon-o-heart'),

            Stat::make('Alfa', number_format($alfa))
                ->icon('heroicon-o-x-circle'),
        ];
    }

    protected function getTotalStatus(Carbon $hariIni, string $status): int
    {
        return PresensiDetail::query()
            ->where('status', $status)
            ->whereHas('sesi', function ($query) use ($hariIni) {
                $query->whereDate('tanggal', $hariIni);
            })
            ->count();
    }
}