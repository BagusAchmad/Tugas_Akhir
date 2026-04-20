<?php

namespace App\Filament\Resources\Gurus;

use App\Filament\Resources\Gurus\Pages\CreateGuru;
use App\Filament\Resources\Gurus\Pages\EditGuru;
use App\Filament\Resources\Gurus\Pages\ListGurus;
use App\Filament\Resources\Gurus\Schemas\GuruForm;
use App\Filament\Resources\Gurus\Tables\GurusTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GuruResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Akun Guru';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 11;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $modelLabel = 'Akun Guru';

    protected static ?string $pluralModelLabel = 'Akun Guru';

    protected static ?string $recordTitleAttribute = 'name';

    private static function isAdminPanel(): bool
    {
        return filament()->getCurrentPanel()?->getId() === 'admin';
    }

    public static function canAccess(): bool
    {
        return self::isAdminPanel();
    }

    public static function canViewAny(): bool
    {
        return self::isAdminPanel();
    }

    public static function canCreate(): bool
    {
        return self::isAdminPanel();
    }

    public static function canEdit($record): bool
    {
        return self::isAdminPanel();
    }

    public static function canDelete($record): bool
    {
        return self::isAdminPanel();
    }

    public static function canDeleteAny(): bool
    {
        return self::isAdminPanel();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', 'guru');
    }

    public static function form(Schema $schema): Schema
    {
        return GuruForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GurusTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGurus::route('/'),
            'create' => CreateGuru::route('/create'),
            'edit' => EditGuru::route('/{record}/edit'),
        ];
    }
}