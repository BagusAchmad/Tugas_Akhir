<?php

namespace App\Filament\Resources\Siswas\Pages;

use App\Filament\Resources\Siswas\SiswaResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateSiswa extends CreateRecord
{
    protected static string $resource = SiswaResource::class;

    protected static ?string $title = 'Buat Akun Siswa';

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    /**
     * Cuma tampilkan tombol Buat & Batal (tanpa "Create another")
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Buat'),

            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }

    protected function handleRecordCreation(array $data): User
    {
        $data['role'] = 'siswa';
        $data['username'] = $data['nis'];
        $data['password'] = Hash::make($data['nis']);

        return User::create($data);
    }
}