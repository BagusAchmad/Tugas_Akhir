<?php

namespace App\Filament\Resources\Kelas\Pages;

use App\Filament\Resources\Kelas\KelasResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateKelas extends CreateRecord
{
    protected static string $resource = KelasResource::class;

    public function getTitle(): string
    {
        return 'Buat Kelas';
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Buat'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}