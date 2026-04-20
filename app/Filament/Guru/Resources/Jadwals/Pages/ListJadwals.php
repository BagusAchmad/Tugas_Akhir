<?php

namespace App\Filament\Guru\Resources\Jadwals\Pages;

use App\Filament\Guru\Resources\Jadwals\JadwalResource;
use Filament\Resources\Pages\ListRecords;

class ListJadwals extends ListRecords
{
    protected static string $resource = JadwalResource::class;

    protected static ?string $title = 'Jadwal Hari Ini';

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}