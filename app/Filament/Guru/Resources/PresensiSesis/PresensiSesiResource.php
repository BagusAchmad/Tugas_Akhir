<?php

namespace App\Filament\Guru\Resources\PresensiSesis;

use App\Filament\Guru\Resources\PresensiSesis\Pages\DetailPresensiSesi;
use App\Filament\Guru\Resources\PresensiSesis\Pages\ListPresensiHari;
use App\Filament\Guru\Resources\PresensiSesis\Pages\ListPresensiKelasMapel;
use App\Filament\Guru\Resources\PresensiSesis\Pages\ViewPresensiKelas;
use App\Models\Kelas;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PresensiSesiResource extends Resource
{
    protected static ?string $model = Kelas::class;

    protected static ?string $navigationLabel = 'Presensi';
    protected static ?string $modelLabel = 'Presensi';
    protected static ?string $pluralModelLabel = 'Presensi';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->role === 'guru';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('jadwals', function (Builder $query) {
                $query->where('guru_id', Auth::id());
            })
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

    public static function getPages(): array
    {
        return [
            'index' => ListPresensiHari::route('/'),
            'hari' => ListPresensiKelasMapel::route('/hari/{hari}'),
            'view' => ViewPresensiKelas::route('/jadwal/{jadwal}'),
            'detail' => DetailPresensiSesi::route('/sesi/{record}/detail'),
        ];
    }
}