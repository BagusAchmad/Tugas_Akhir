<?php

namespace App\Filament\Resources\RekapAbsensis\Tables;

use App\Filament\Resources\RekapAbsensis\RekapAbsensiResource;
use App\Models\Jadwal;
use App\Models\PresensiSesi;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class RekapAbsensisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->recordUrl(null)
            ->columns([
                TextColumn::make('row_index')
                    ->label('No')
                    ->state(fn ($rowLoop) => $rowLoop->iteration),

                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jurusan')
                    ->label('Jurusan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nomor')
                    ->label('Nomor')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('nama')
                    ->label('Nama Kelas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('wali_kelas')
                    ->label('Wali Kelas')
                    ->state(function ($record): string {
                        return User::query()
                            ->where('role', 'guru')
                            ->where('wali_kelas_id', $record->id)
                            ->value('name') ?? '-';
                    }),

                TextColumn::make('jumlah_siswa')
                    ->label('Jumlah Siswa')
                    ->state(fn ($record): int => User::query()
                        ->where('role', 'siswa')
                        ->where('kelas_id', $record->id)
                        ->count())
                    ->alignCenter(),

                TextColumn::make('jumlah_jadwal_aktif')
                    ->label('Jadwal Aktif')
                    ->state(fn ($record): int => Jadwal::query()
                        ->where('kelas_id', $record->id)
                        ->where('aktif', true)
                        ->count())
                    ->alignCenter(),

                TextColumn::make('total_pertemuan')
                    ->label('Total Pertemuan')
                    ->state(fn ($record): int => self::hitungTotalPertemuanKelas($record->id))
                    ->alignCenter(),

                TextColumn::make('sudah_dibuka')
                    ->label('Sudah Dibuka')
                    ->state(fn ($record): int => PresensiSesi::query()
                        ->whereHas('jadwal', function ($query) use ($record) {
                            $query->where('kelas_id', $record->id);
                        })
                        ->whereIn('status', ['open', 'closed'])
                        ->count())
                    ->alignCenter(),

                TextColumn::make('progress')
                    ->label('Progress')
                    ->state(function ($record): string {
                        $total = self::hitungTotalPertemuanKelas($record->id);

                        $dibuka = PresensiSesi::query()
                            ->whereHas('jadwal', function ($query) use ($record) {
                                $query->where('kelas_id', $record->id);
                            })
                            ->whereIn('status', ['open', 'closed'])
                            ->count();

                        return $dibuka . ' / ' . $total;
                    })
                    ->badge(),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tingkat')
                    ->label('Tingkat')
                    ->options([
                        'X' => 'X',
                        'XI' => 'XI',
                        'XII' => 'XII',
                    ])
                    ->native(false),

                SelectFilter::make('jurusan')
                    ->label('Jurusan')
                    ->options([
                        'PPLG' => 'PPLG',
                        'MPLB' => 'MPLB',
                    ])
                    ->native(false),

                TernaryFilter::make('aktif')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                Action::make('lihatRekap')
                    ->label('Lihat Rekap')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => RekapAbsensiResource::getUrl('view', [
                        'record' => $record,
                    ])),
            ])
            ->toolbarActions([]);
    }

    protected static function hitungTotalPertemuanKelas(int $kelasId): int
    {
        $jadwals = Jadwal::query()
            ->where('kelas_id', $kelasId)
            ->where('aktif', true)
            ->get();

        $total = 0;

        foreach ($jadwals as $jadwal) {
            if (! $jadwal->berlaku_dari || ! $jadwal->berlaku_sampai) {
                continue;
            }

            $total += count(self::generateTanggalSesi(
                $jadwal->hari,
                $jadwal->berlaku_dari,
                $jadwal->berlaku_sampai,
            ));
        }

        return $total;
    }

    protected static function generateTanggalSesi(string $hari, $mulai, $sampai): array
    {
        $hasil = [];

        $mapHari = [
            'senin' => Carbon::MONDAY,
            'selasa' => Carbon::TUESDAY,
            'rabu' => Carbon::WEDNESDAY,
            'kamis' => Carbon::THURSDAY,
            'jumat' => Carbon::FRIDAY,
        ];

        $targetDay = $mapHari[$hari] ?? null;

        if (! $targetDay) {
            return [];
        }

        $current = Carbon::parse($mulai)->startOfDay();
        $end = Carbon::parse($sampai)->startOfDay();

        while ($current->dayOfWeek !== $targetDay) {
            $current->addDay();

            if ($current->gt($end)) {
                return [];
            }
        }

        while ($current->lte($end)) {
            $hasil[] = $current->toDateString();
            $current->addWeek();
        }

        return $hasil;
    }
}