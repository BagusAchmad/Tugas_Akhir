<?php

namespace App\Filament\Siswa\Resources\PresensiDetails\Pages;

use App\Filament\Siswa\Resources\PresensiDetails\PresensiDetailResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPresensiDetail extends EditRecord
{
    protected static string $resource = PresensiDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
