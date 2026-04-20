<?php

namespace App\Filament\Resources\RekapAbsensis;

use App\Filament\Resources\RekapAbsensis\Pages\ListRekapAbsensis;
use App\Filament\Resources\RekapAbsensis\Pages\ViewRekapAbsensiHari;
use App\Filament\Resources\RekapAbsensis\Pages\ViewRekapAbsensiKelas;
use App\Filament\Resources\RekapAbsensis\Pages\ViewRekapAbsensiPelajaran;
use App\Filament\Resources\RekapAbsensis\Pages\ViewRekapAbsensiSiswa;
use App\Filament\Resources\RekapAbsensis\Tables\RekapAbsensisTable;
use App\Models\Kelas;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RekapAbsensiResource extends Resource
{
    protected static ?string $model = Kelas::class;

    protected static ?string $navigationLabel = 'Rekap Absensi';
    protected static ?string $modelLabel = 'Rekap Absensi';
    protected static ?string $pluralModelLabel = 'Rekap Absensi';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 21;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

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
            'index' => ListRekapAbsensis::route('/'),
            'view' => ViewRekapAbsensiKelas::route('/{record}'),
            'hari' => ViewRekapAbsensiHari::route('/{record}/hari/{hari}'),
            'pelajaran' => ViewRekapAbsensiPelajaran::route('/{record}/jadwal/{jadwal}'),
            'siswa' => ViewRekapAbsensiSiswa::route('/{record}/sesi/{sesi}'),
        ];
    }
}