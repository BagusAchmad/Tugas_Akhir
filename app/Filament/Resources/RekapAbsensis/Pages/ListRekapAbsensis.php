<?php

namespace App\Filament\Resources\RekapAbsensis\Pages;

use App\Filament\Resources\RekapAbsensis\RekapAbsensiResource;
use Filament\Resources\Pages\ListRecords;

class ListRekapAbsensis extends ListRecords
{
    protected static string $resource = RekapAbsensiResource::class;

    protected static ?string $title = 'Rekap Absensi';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getSubheading(): ?string
    {
        return 'Pilih kelas untuk melihat rekap absensi, wali kelas, siswa, dan progress presensi secara keseluruhan.';
    }
}