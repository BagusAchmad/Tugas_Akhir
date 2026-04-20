<?php

namespace App\Filament\Resources\Siswas\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class SiswasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->recordUrl(null)
            ->defaultSort('name', 'asc')

            ->columns([
                TextColumn::make('row_index')
                    ->label('No')
                    ->state(fn ($rowLoop) => $rowLoop->iteration),

                TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kelas.nama')
                    ->label('Kelas')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()->label('Edit'),

                Action::make('hapusPermanen')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Akun Siswa?')
                    ->modalDescription('Data siswa akan dihapus permanen (tidak bisa dikembalikan).')
                    ->action(function ($record) {
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
                        ->modalDescription('Data siswa yang dipilih akan dihapus permanen (tidak bisa dikembalikan).')
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