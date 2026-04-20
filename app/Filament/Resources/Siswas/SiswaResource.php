<?php

namespace App\Filament\Resources\Siswas;

use App\Filament\Resources\Siswas\Pages\CreateSiswa;
use App\Filament\Resources\Siswas\Pages\EditSiswa;
use App\Filament\Resources\Siswas\Pages\ListSiswas;
use App\Filament\Resources\Siswas\Schemas\SiswaForm;
use App\Filament\Resources\Siswas\Tables\SiswasTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SiswaResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Akun Siswa';
    protected static ?int $navigationSort = 4;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'Akun Siswa';
    protected static ?string $pluralModelLabel = 'Akun Siswa';

    protected static ?string $recordTitleAttribute = 'name';

    private static function isAdmin(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }

    public static function canAccess(): bool
    {
        return self::isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return self::isAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', 'siswa');
    }

    public static function form(Schema $schema): Schema
    {
        return SiswaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiswasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiswas::route('/'),
            'create' => CreateSiswa::route('/create'),
            'edit' => EditSiswa::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
