<?php

namespace App\Filament\Guru\Resources\PresensiSesis\Pages;

use App\Filament\Guru\Resources\PresensiSesis\PresensiSesiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPresensiSesi extends EditRecord
{
    protected static string $resource = PresensiSesiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
