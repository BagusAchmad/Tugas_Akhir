<?php

namespace App\Filament\Siswa\Resources\RekapAbsensis\Pages;

use App\Filament\Siswa\Resources\RekapAbsensis\RekapAbsensiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRekapAbsensi extends EditRecord
{
    protected static string $resource = RekapAbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
