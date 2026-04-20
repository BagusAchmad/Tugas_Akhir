<?php

namespace App\Filament\Guru\Resources\Jadwals\Pages;

use App\Filament\Guru\Resources\Jadwals\JadwalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJadwal extends EditRecord
{
    protected static string $resource = JadwalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
