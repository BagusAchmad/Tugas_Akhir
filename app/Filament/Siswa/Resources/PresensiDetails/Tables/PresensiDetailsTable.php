<?php

namespace App\Filament\Siswa\Resources\PresensiDetails\Tables;

use App\Models\PresensiDetail;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PresensiDetailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sesi.tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('sesi.jadwal.hari')
                    ->label('Hari')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),

                TextColumn::make('sesi.jadwal.jam_ke')
                    ->label('Jam')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => 'Jam ke-' . $state),

                TextColumn::make('sesi.jadwal.mapel.nama')
                    ->label('Mapel')
                    ->searchable(),

                TextColumn::make('sesi.jadwal.guru.name')
                    ->label('Guru')
                    ->searchable(),

                TextColumn::make('sesi.status')
                    ->label('Status Sesi')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'Belum Dibuka',
                        'open' => 'Sedang Dibuka',
                        'closed' => 'Ditutup',
                        default => (string) $state,
                    }),

                TextColumn::make('status')
                    ->label('Presensi Kamu')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alfa' => 'Belum Absen',
                        default => (string) $state,
                    }),

                TextColumn::make('waktu_isi')
                    ->label('Diisi Pada')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status_sesi')
                    ->label('Status Sesi')
                    ->options([
                        'draft' => 'Belum Dibuka',
                        'open' => 'Sedang Dibuka',
                        'closed' => 'Ditutup',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! filled($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('sesi', fn (Builder $q) => $q->where('status', $data['value']));
                    }),

                SelectFilter::make('status_presensi')
                    ->label('Status Presensi')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alfa' => 'Belum Absen',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! filled($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->where('status', $data['value']);
                    }),
            ])
            ->recordActions([
                Action::make('isi_presensi')
                    ->label('Isi Presensi')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (PresensiDetail $record) => ($record->sesi?->status ?? null) === 'open')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'hadir' => 'Hadir',
                                'izin' => 'Izin',
                                'sakit' => 'Sakit',
                                'alfa' => 'Alfa',
                            ])
                            ->required(),

                        Textarea::make('keterangan')
                            ->label('Keterangan (opsional)')
                            ->rows(3)
                            ->maxLength(255),
                    ])
                    ->fillForm(fn (PresensiDetail $record): array => [
                        'status' => $record->status,
                        'keterangan' => $record->keterangan,
                    ])
                    ->action(function (PresensiDetail $record, array $data): void {
                        abort_unless($record->siswa_id === Auth::id(), 403);
                        abort_unless(($record->sesi?->status ?? null) === 'open', 403);

                        $record->update([
                            'status' => $data['status'],
                            'keterangan' => $data['keterangan'] ?? null,
                            'metode' => 'siswa',
                            'waktu_isi' => now(),
                        ]);
                    })
                    ->modalHeading('Isi Presensi')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal'),
            ])
            ->toolbarActions([])
            ->recordUrl(null);
    }
}