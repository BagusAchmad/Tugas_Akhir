<?php

namespace App\Filament\Guru\Resources\Jadwals;

use App\Filament\Guru\Resources\Jadwals\Pages\ListJadwals;
use App\Filament\Guru\Resources\Jadwals\Tables\JadwalsTable;
use App\Models\Jadwal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class JadwalResource extends Resource
{
    protected static ?string $model = Jadwal::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Jadwal Hari Ini';
    protected static ?string $modelLabel = 'Jadwal';
    protected static ?string $pluralModelLabel = 'Jadwal Hari Ini';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'id';

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->role === 'guru';
    }

    public static function getEloquentQuery(): Builder
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

        $hariIni = $hariMap[now()->format('l')] ?? null;

        return parent::getEloquentQuery()
            ->where('guru_id', Auth::id())
            ->when($hariIni, fn (Builder $q) => $q->where('hari', $hariIni))
            ->orderBy('jam_ke');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return JadwalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJadwals::route('/'),
        ];
    }
}