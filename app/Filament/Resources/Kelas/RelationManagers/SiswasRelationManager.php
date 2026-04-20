<?php

namespace App\Filament\Resources\Kelas\RelationManagers;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class SiswasRelationManager extends RelationManager
{
    protected static string $relationship = 'siswas';
    protected static ?string $title = 'Siswa';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return str_contains($pageClass, 'ManageRelatedRecords');
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
                ->helperText('Login pakai NIS. Password default = NIS.'),
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
            ->headerActions([
                CreateAction::make()
                    ->label('Buat Akun Siswa')
                    ->using(function (array $data): User {
                        $kelasId = $this->getOwnerRecord()->id;

                        return User::create([
                            'name' => $data['name'],
                            'nis' => $data['nis'],
                            'kelas_id' => $kelasId,
                            'role' => 'siswa',
                            'username' => $data['nis'],
                            'password' => Hash::make($data['nis']),
                        ]);
                    })
                    ->modalSubmitActionLabel('Buat')
                    ->modalCancelActionLabel('Batal'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal'),

                Action::make('hapusPermanen')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus akun siswa?')
                    ->modalDescription('Akun siswa akan dihapus permanen (tidak bisa dikembalikan).')
                    ->action(function (User $record) {
                        $record->forceDelete();

                        Notification::make()
                            ->title('Berhasil dihapus')
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
                        ->action(function (Collection $records) {
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