<?php

namespace App\Filament\Resources\Jadwals\Pages;

use App\Filament\Resources\Jadwals\JadwalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJadwal extends EditRecord
{
    protected static string $resource = JadwalResource::class;

    protected static ?string $title = 'Edit Jadwal Pelajaran';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()->label('Simpan');
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()->label('Batal');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}