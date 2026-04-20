<?php

namespace App\Filament\Resources\Mapels\Pages;

use App\Filament\Resources\Mapels\MapelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMapels extends ListRecords
{
    protected static string $resource = MapelResource::class;

    protected static ?string $title = 'Mata Pelajaran';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Mata Pelajaran'),
        ];
    }
}