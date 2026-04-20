<?php

namespace App\Filament\Resources\Jadwals;

use App\Filament\Resources\Jadwals\Pages\ListJadwals;
use App\Filament\Resources\Jadwals\Pages\ViewJadwalHari;
use App\Filament\Resources\Jadwals\Pages\ViewJadwalKelas;
use App\Filament\Resources\Jadwals\Schemas\JadwalForm;
use App\Filament\Resources\Jadwals\Tables\JadwalsTable;
use App\Models\Kelas;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class JadwalResource extends Resource
{
    protected static ?string $model = Kelas::class;

    protected static ?string $navigationLabel = 'Jadwal Pelajaran';
    protected static ?string $modelLabel = 'Jadwal Pelajaran';
    protected static ?string $pluralModelLabel = 'Jadwal Pelajaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderByRaw("
                CASE tingkat
                    WHEN 'X' THEN 1
                    WHEN 'XI' THEN 2
                    WHEN 'XII' THEN 3
                    ELSE 99
                END
            ")
            ->orderBy('jurusan')
            ->orderBy('nomor');
    }

    public static function form(Schema $schema): Schema
    {
        return JadwalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JadwalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJadwals::route('/'),
            'view' => ViewJadwalKelas::route('/{record}'),
            'hari' => ViewJadwalHari::route('/{record}/hari/{hari}'),
        ];
    }
}