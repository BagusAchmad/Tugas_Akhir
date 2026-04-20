<?php

namespace App\Filament\Siswa\Resources\PresensiDetails;

use App\Filament\Siswa\Resources\PresensiDetails\Pages\ListPresensiDetails;
use App\Filament\Siswa\Resources\PresensiDetails\Pages\ViewPresensiHari;
use App\Models\PresensiDetail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class PresensiDetailResource extends Resource
{
    protected static ?string $model = PresensiDetail::class;

    protected static ?string $navigationLabel = 'Presensi';
    protected static ?string $modelLabel = 'Presensi';
    protected static ?string $pluralModelLabel = 'Presensi';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPresensiDetails::route('/'),
            'hari' => ViewPresensiHari::route('/hari/{hari}'),
        ];
    }
}