<?php

namespace App\Filament\Resources\Mapels\Pages;

use App\Filament\Resources\Mapels\MapelResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMapel extends CreateRecord
{
    protected static string $resource = MapelResource::class;

    // Matikan "Create & create another" (cara Filament v5)
    protected static bool $canCreateAnother = false;

    // Title di halaman
    protected static ?string $title = 'Buat Mata Pelajaran';

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()->label('Buat');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}