<?php

namespace App\Filament\Resources\Kelas\Pages;

use App\Filament\Resources\Kelas\KelasResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class ViewKelas extends ManageRelatedRecords
{
    protected static string $resource = KelasResource::class;

    protected static string $relationship = 'siswas';

    protected static ?string $title = 'Detail Siswa';

    public function getTitle(): string
    {
        return 'Detail Siswa - ' . ($this->record->nama ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Akun Siswa')
                ->modalHeading('Buat Akun Siswa')
                ->createAnother(false)
                ->modalSubmitActionLabel('Buat')
                ->modalCancelActionLabel('Batal')
                ->using(function (array $data): User {
                    if (User::where('username', $data['nis'])->exists()) {
                        Notification::make()
                            ->title('NIS sudah dipakai')
                            ->body('NIS ini sudah terdaftar sebagai username akun lain.')
                            ->danger()
                            ->send();

                        throw new \Exception('NIS sudah dipakai.');
                    }

                    return User::create([
                        'name' => $data['name'],
                        'nis' => $data['nis'],
                        'kelas_id' => $this->record->id,
                        'role' => 'siswa',
                        'username' => $data['nis'],
                        'password' => Hash::make($data['nis']),
                    ]);
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Siswa')
                ->required()
                ->maxLength(255),

            TextInput::make('nis')
                ->label('NIS')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true)
                ->helperText('Login pakai NIS. Password default = NIS.')
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('username', $state);
                }),

            Hidden::make('username'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->recordUrl(null)
            ->recordTitleAttribute('name')
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->extraModalFooterActions([
                        Action::make('resetPassword')
                            ->label('Reset Password = NIS')
                            ->icon('heroicon-o-key')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Reset Password')
                            ->modalDescription('Password akan diubah menjadi sama dengan NIS.')
                            ->action(function (User $record): void {
                                $nis = $record->nis;

                                $record->update([
                                    'password' => Hash::make($nis),
                                    'username' => $nis,
                                ]);

                                Notification::make()
                                    ->title('Password berhasil di-reset')
                                    ->body("Password sekarang = NIS: {$nis}")
                                    ->success()
                                    ->send();
                            })
                            ->cancelParentActions(),
                    ]),

                Action::make('hapusPermanen')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus akun siswa?')
                    ->modalDescription('Akun siswa akan dihapus permanen (tidak bisa dikembalikan).')
                    ->action(function (User $record): void {
                        $record->forceDelete();

                        Notification::make()
                            ->title('Akun siswa berhasil dihapus')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('hapusTerpilihPermanen')
                        ->label('Hapus terpilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus semua yang dipilih?')
                        ->modalDescription('Akun siswa yang dipilih akan dihapus permanen (tidak bisa dikembalikan).')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->forceDelete();
                            }

                            Notification::make()
                                ->title('Data terpilih berhasil dihapus')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}