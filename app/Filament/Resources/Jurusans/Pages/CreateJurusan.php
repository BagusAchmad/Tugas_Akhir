<?php

namespace App\Filament\Resources\Jurusans\Pages;

use App\Filament\Resources\Jurusans\JurusanResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateJurusan extends CreateRecord
{
    protected static string $resource = JurusanResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Buat');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->hidden();
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal')
            ->url(static::getResource()::getUrl('index'));
    }
}