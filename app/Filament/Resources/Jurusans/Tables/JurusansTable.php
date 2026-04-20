<?php

namespace App\Filament\Resources\Jurusans\Tables;

use App\Models\Kelas;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class JurusansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->recordUrl(null)
            ->defaultSort('singkatan', 'asc')
            ->columns([
                TextColumn::make('row_index')
                    ->label('No')
                    ->state(fn ($rowLoop) => $rowLoop->iteration),

                TextColumn::make('singkatan')
                    ->label('Singkatan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama')
                    ->label('Nama Jurusan')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()->label('Edit'),

                Action::make('hapus')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Jurusan?')
                    ->modalDescription('Jurusan dan semua rombel dengan jurusan ini akan dihapus permanen.')
                    ->action(function ($record) {
                        Kelas::query()
                            ->where('jurusan', $record->singkatan)
                            ->get()
                            ->each(function (Kelas $kelas) {
                                $kelas->forceDelete();
                            });

                        $record->delete();

                        Notification::make()
                            ->title('Jurusan berhasil dihapus')
                            ->body('Semua rombel dengan jurusan ini juga ikut dihapus.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('hapusTerpilih')
                        ->label('Hapus terpilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus jurusan terpilih?')
                        ->modalDescription('Semua jurusan terpilih beserta rombel yang terkait akan dihapus permanen.')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                Kelas::query()
                                    ->where('jurusan', $record->singkatan)
                                    ->get()
                                    ->each(function (Kelas $kelas) {
                                        $kelas->forceDelete();
                                    });

                                $record->delete();
                            }

                            Notification::make()
                                ->title('Jurusan terpilih berhasil dihapus')
                                ->body('Semua rombel yang terkait juga ikut dihapus.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}