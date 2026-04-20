<?php

namespace App\Filament\Widgets;

use App\Models\Jadwal;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOperasionalHariIni extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $hariIni = Carbon::today();

        $jadwalHariIni = Jadwal::query()
            ->where('aktif', true)
            ->where('hari', $this->getHariIni())
            ->where(function ($query) use ($hariIni) {
                $query->whereNull('berlaku_dari')
                    ->orWhereDate('berlaku_dari', '<=', $hariIni);
            })
            ->where(function ($query) use ($hariIni) {
                $query->whereNull('berlaku_sampai')
                    ->orWhereDate('berlaku_sampai', '>=', $hariIni);
            })
            ->count();

        $sesiHariIni = PresensiSesi::query()
            ->whereDate('tanggal', $hariIni)
            ->count();

        $sesiDibuka = PresensiSesi::query()
            ->whereDate('tanggal', $hariIni)
            ->whereNotNull('dibuka_pada')
            ->count();

        $sesiDitutup = PresensiSesi::query()
            ->whereDate('tanggal', $hariIni)
            ->whereNotNull('ditutup_pada')
            ->count();

        $sesiBerjalan = PresensiSesi::query()
            ->whereDate('tanggal', $hariIni)
            ->whereNotNull('dibuka_pada')
            ->whereNull('ditutup_pada')
            ->count();

        $isianHariIni = PresensiDetail::query()
            ->whereHas('sesi', function ($query) use ($hariIni) {
                $query->whereDate('tanggal', $hariIni);
            })
            ->count();

        return [
            Stat::make('Jadwal Hari Ini', number_format($jadwalHariIni))
                ->description('Jadwal aktif hari ini')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Sesi Presensi Hari Ini', number_format($sesiHariIni))
                ->description('Sesi yang tercatat hari ini')
                ->icon('heroicon-o-clipboard-document-check'),

            Stat::make('Sesi Dibuka', number_format($sesiDibuka))
                ->description('Sudah dibuka')
                ->icon('heroicon-o-lock-open'),

            Stat::make('Sesi Ditutup', number_format($sesiDitutup))
                ->description('Sudah ditutup')
                ->icon('heroicon-o-lock-closed'),

            Stat::make('Sesi Berjalan', number_format($sesiBerjalan))
                ->description('Masih berlangsung')
                ->icon('heroicon-o-clock'),

            Stat::make('Isian Presensi Hari Ini', number_format($isianHariIni))
                ->description('Detail presensi terisi')
                ->icon('heroicon-o-pencil-square'),
        ];
    }

    protected function getHariIni(): string
    {
        return match (strtolower(Carbon::today()->locale('id')->dayName)) {
            'senin' => 'senin',
            'selasa' => 'selasa',
            'rabu' => 'rabu',
            'kamis' => 'kamis',
            'jumat' => 'jumat',
            default => strtolower(Carbon::today()->translatedFormat('l')),
        };
    }
}