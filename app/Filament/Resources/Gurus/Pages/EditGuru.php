<?php

namespace App\Filament\Resources\Gurus\Pages;

use App\Filament\Resources\Gurus\GuruResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditGuru extends EditRecord
{
    protected static string $resource = GuruResource::class;

    public function getHeading(): string
    {
        return 'Edit Akun Guru';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetPassword')
                ->label('Reset Password = Username')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reset Password')
                ->modalDescription('Password akan diubah menjadi sama seperti Username/Email login guru ini.')
                ->action(function () {
                    $username = $this->record->username ?: $this->record->email;

                    $this->record->password = Hash::make($username);
                    $this->record->save();

                    Notification::make()
                        ->title('Password berhasil direset')
                        ->body("Password sekarang sama dengan username/email: {$username}")
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['role'] = 'guru';

        if (! empty($data['email'])) {
            $data['username'] = $data['email'];
        }

        return $data;
    }
}