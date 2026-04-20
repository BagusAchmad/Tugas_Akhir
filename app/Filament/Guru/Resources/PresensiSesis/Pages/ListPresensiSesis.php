<?php

namespace App\Filament\Guru\Resources\PresensiSesis\Pages;

use App\Filament\Guru\Resources\PresensiSesis\PresensiSesiResource;
use Filament\Resources\Pages\ListRecords;

class ListPresensiSesis extends ListRecords
{
    protected static string $resource = PresensiSesiResource::class;

    protected static ?string $title = 'Presensi';

    protected function getHeaderActions(): array
    {
        return [];
    }
}