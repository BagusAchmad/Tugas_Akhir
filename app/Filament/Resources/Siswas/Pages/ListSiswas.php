<?php

namespace App\Filament\Resources\Siswas\Pages;

use App\Filament\Resources\Siswas\SiswaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiswas extends ListRecords
{
    protected static string $resource = SiswaResource::class;

    protected static ?string $title = 'Akun Siswa';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Akun Siswa'),
        ];
    }
}