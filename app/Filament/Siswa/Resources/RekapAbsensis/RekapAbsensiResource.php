<?php

namespace App\Filament\Siswa\Resources\RekapAbsensis;

use App\Filament\Siswa\Resources\RekapAbsensis\Pages\ListRekapAbsensis;
use App\Filament\Siswa\Resources\RekapAbsensis\Pages\ViewRekapAbsensiHari;
use App\Filament\Siswa\Resources\RekapAbsensis\Tables\RekapAbsensisTable;
use App\Models\PresensiDetail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RekapAbsensiResource extends Resource
{
    protected static ?string $model = PresensiDetail::class;

    protected static ?string $navigationLabel = 'Rekap Absensi';
    protected static ?string $modelLabel = 'Rekap Absensi';
    protected static ?string $pluralModelLabel = 'Rekap Absensi';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 11;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'id';

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->role === 'siswa';
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
            'hari' => ViewRekapAbsensiHari::route('/hari/{hari}'),
        ];
    }
}