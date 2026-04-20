<?php

namespace App\Filament\Guru\Pages;

use App\Filament\Guru\Widgets\GuruStatsOverview;
use App\Filament\Guru\Widgets\GuruWelcome;
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
            GuruWelcome::class,
            GuruStatsOverview::class,
        ];
    }
}