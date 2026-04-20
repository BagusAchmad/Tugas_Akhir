<?php

namespace App\Filament\Guru\Resources\RekapAbsensis;

use App\Filament\Guru\Resources\RekapAbsensis\Pages\ListRekapAbsensis;
use App\Filament\Guru\Resources\RekapAbsensis\Pages\ViewRekapAbsensi;
use App\Filament\Guru\Resources\RekapAbsensis\Tables\RekapAbsensisTable;
use App\Models\Jadwal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RekapAbsensiResource extends Resource
{
    protected static ?string $model = Jadwal::class;

    protected static ?string $navigationLabel = 'Rekap Absensi';
    protected static ?string $modelLabel = 'Rekap Absensi';
    protected static ?string $pluralModelLabel = 'Rekap Absensi';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 11;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'id';

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->role === 'guru';
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
        return RekapAbsensisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRekapAbsensis::route('/'),
            'view' => ViewRekapAbsensi::route('/{record}'),
        ];
    }
}