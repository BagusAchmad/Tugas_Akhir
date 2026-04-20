<?php

namespace App\Filament\Resources\RekapAbsensis\Pages;

use App\Filament\Resources\RekapAbsensis\RekapAbsensiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRekapAbsensi extends EditRecord
{
    protected static string $resource = RekapAbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
