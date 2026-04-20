<?php

namespace App\Filament\Siswa\Pages;

use App\Filament\Siswa\Widgets\SiswaStatsOverview;
use App\Filament\Siswa\Widgets\SiswaWelcome;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Menu Utama';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            SiswaWelcome::class,
            SiswaStatsOverview::class,
        ];
    }
}