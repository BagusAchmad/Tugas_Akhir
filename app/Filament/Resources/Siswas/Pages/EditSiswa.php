<?php

namespace App\Filament\Resources\Siswas\Pages;

use App\Filament\Resources\Siswas\SiswaResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditSiswa extends EditRecord
{
    protected static string $resource = SiswaResource::class;

    public function getHeading(): string
    {
        return 'Edit Akun Siswa';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetPassword')
                ->label('Reset Password = NIS')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reset Password')
                ->modalDescription('Password akan diubah menjadi sama dengan NIS.')
                ->action(function () {
                    $nis = $this->record->nis;

                    $this->record->password = Hash::make($nis);
                    $this->record->username = $nis;
                    $this->record->save();

                    Notification::make()
                        ->title('Password berhasil di-reset')
                        ->body("Password sekarang = NIS: {$nis}")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label('Simpan'),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}