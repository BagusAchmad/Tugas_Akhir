<?php

namespace App\Filament\Resources\Jadwals\Pages;

use App\Filament\Resources\Jadwals\JadwalResource;
use Filament\Resources\Pages\ListRecords;

class ListJadwals extends ListRecords
{
    protected static string $resource = JadwalResource::class;

    protected static ?string $title = 'Jadwal Pelajaran';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getSubheading(): ?string
    {
        return 'Pilih rombel untuk melihat dan mengelola jadwal pelajaran per kelas.';
    }
}