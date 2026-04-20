<?php

namespace App\Filament\Guru\Resources\RekapAbsensis\Pages;

use App\Filament\Guru\Resources\RekapAbsensis\RekapAbsensiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRekapAbsensi extends EditRecord
{
    protected static string $resource = RekapAbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
