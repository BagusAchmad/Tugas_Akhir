<?php

namespace App\Filament\Guru\Resources\Jadwals\Tables;

use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class JadwalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kelas.nama')
                    ->label('Rombel')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('jam_ke')
                    ->label('Jam')
                    ->sortable(),

                TextColumn::make('mapel.nama')
                    ->label('Mata Pelajaran')
                    ->sortable()
                    ->searchable(),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('bukaPresensi')
                    ->label('Buka Presensi')
                    ->requiresConfirmation()
                    ->visible(function ($record) {
                        if (! $record->aktif) return false;

                        $tanggal = now()->toDateString();

                        $sesi = PresensiSesi::query()
                            ->where('jadwal_id', $record->id)
                            ->where('tanggal', $tanggal)
                            ->first();

                        return ! $sesi || $sesi->status === 'draft';
                    })
                    ->action(function ($record) {
                        $tanggal = now()->toDateString();

                        $sesi = PresensiSesi::firstOrCreate(
                            [
                                'jadwal_id' => $record->id,
                                'tanggal' => $tanggal,
                            ],
                            [
                                'status' => 'draft',
                            ]
                        );

                        if ($sesi->status === 'closed') {
                            Notification::make()
                                ->title('Presensi sudah ditutup')
                                ->body('Presensi hari ini sudah final dan tidak bisa dibuka lagi.')
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($sesi->status === 'open') {
                            Notification::make()
                                ->title('Presensi sudah dibuka')
                                ->body('Presensi hari ini sedang dibuka.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $sesi->update([
                            'status' => 'open',
                            'dibuka_pada' => now(),
                            'dibuka_oleh' => Auth::id(),
                        ]);

                        $siswaIds = User::query()
                            ->where('role', 'siswa')
                            ->where('kelas_id', $record->kelas_id)
                            ->pluck('id');

                        foreach ($siswaIds as $siswaId) {
                            PresensiDetail::firstOrCreate(
                                [
                                    'presensi_sesi_id' => $sesi->id,
                                    'siswa_id' => $siswaId,
                                ],
                                [
                                    'status' => 'alfa',
                                    'metode' => 'guru',
                                    'waktu_isi' => null,
                                ]
                            );
                        }

                        Notification::make()
                            ->title('Presensi dibuka')
                            ->body("Tanggal: {$tanggal}")
                            ->success()
                            ->send();
                    }),

                Action::make('tutupPresensi')
                    ->label('Tutup Presensi')
                    ->requiresConfirmation()
                    ->visible(function ($record) {
                        $tanggal = now()->toDateString();

                        return PresensiSesi::query()
                            ->where('jadwal_id', $record->id)
                            ->where('tanggal', $tanggal)
                            ->where('status', 'open')
                            ->exists();
                    })
                    ->action(function ($record) {
                        $tanggal = now()->toDateString();

                        $sesi = PresensiSesi::query()
                            ->where('jadwal_id', $record->id)
                            ->where('tanggal', $tanggal)
                            ->first();

                        if (! $sesi) {
                            Notification::make()
                                ->title('Belum ada sesi presensi')
                                ->body('Buka presensi dulu sebelum ditutup.')
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($sesi->status === 'closed') {
                            Notification::make()
                                ->title('Presensi sudah ditutup')
                                ->body('Presensi ini sudah final.')
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($sesi->status !== 'open') {
                            Notification::make()
                                ->title('Presensi belum dibuka')
                                ->body('Buka presensi dulu sebelum ditutup.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $sesi->update([
                            'status' => 'closed',
                            'ditutup_pada' => now(),
                            'ditutup_oleh' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Presensi ditutup')
                            ->body("Tanggal: {$tanggal}")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                //
            ]);
    }
}