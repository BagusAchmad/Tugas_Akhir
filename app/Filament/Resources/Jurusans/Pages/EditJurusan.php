<?php

namespace App\Filament\Resources\Jurusans\Pages;

use App\Filament\Resources\Jurusans\JurusanResource;
use App\Models\Kelas;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditJurusan extends EditRecord
{
    protected static string $resource = JurusanResource::class;

    protected ?string $singkatanLama = null;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function beforeFill(): void
    {
        $this->singkatanLama = $this->record->singkatan;
    }

    protected function afterSave(): void
    {
        $singkatanBaru = $this->record->singkatan;

        if ($this->singkatanLama && $this->singkatanLama !== $singkatanBaru) {
            Kelas::query()
                ->where('jurusan', $this->singkatanLama)
                ->update([
                    'jurusan' => $singkatanBaru,
                ]);

            Kelas::query()
                ->where('jurusan', $singkatanBaru)
                ->get()
                ->each(function (Kelas $kelas) {
                    $kelas->update([
                        'nama' => trim($kelas->tingkat . ' ' . $kelas->jurusan . ' ' . $kelas->nomor),
                    ]);
                });
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal')
            ->url(static::getResource()::getUrl('index'));
    }
}