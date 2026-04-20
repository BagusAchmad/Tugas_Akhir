<?php

namespace App\Filament\Resources\Jadwals\Pages;

use App\Filament\Resources\Jadwals\JadwalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJadwal extends CreateRecord
{
    protected static string $resource = JadwalResource::class;

    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Buat Jadwal Pelajaran';

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()->label('Buat');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}