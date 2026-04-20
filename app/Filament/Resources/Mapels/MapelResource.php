<?php

namespace App\Filament\Resources\Mapels;

use App\Filament\Resources\Mapels\Pages\CreateMapel;
use App\Filament\Resources\Mapels\Pages\EditMapel;
use App\Filament\Resources\Mapels\Pages\ListMapels;
use App\Filament\Resources\Mapels\Schemas\MapelForm;
use App\Filament\Resources\Mapels\Tables\MapelsTable;
use App\Models\Mapel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MapelResource extends Resource
{
    protected static ?string $model = Mapel::class;

    protected static ?string $navigationLabel = 'Mata Pelajaran';

    protected static ?string $modelLabel = 'Mata Pelajaran';

    protected static ?string $pluralModelLabel = 'Mata Pelajaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 12;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'nama';

    private static function isAdmin(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }

    public static function canAccess(): bool
    {
        return self::isAdmin();
    }

    public static function canViewAny(): bool
    {
        return self::isAdmin();
    }

    public static function canCreate(): bool
    {
        return self::isAdmin();
    }

    public static function canEdit($record): bool
    {
        return self::isAdmin();
    }

    public static function canDelete($record): bool
    {
        return self::isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return self::isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return MapelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MapelsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMapels::route('/'),
            'create' => CreateMapel::route('/create'),
            'edit' => EditMapel::route('/{record}/edit'),
        ];
    }
}