<?php

namespace App\Filament\Resources\Jadwals\Pages;

use App\Filament\Resources\Jadwals\JadwalResource;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\PresensiDetail;
use App\Models\PresensiSesi;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Unique;

class ViewJadwalHari extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = JadwalResource::class;

    protected string $view = 'resources.pages.page';

    public ?int $record = null;

    public ?Kelas $kelas = null;

    public ?string $hari = null;

    public function mount($record, string $hari): void
    {
        $this->record = (int) $record;
        $this->hari = strtolower($hari);

        abort_if(! in_array($this->hari, ['senin', 'selasa', 'rabu', 'kamis', 'jumat']), 404);

        $this->kelas = Kelas::findOrFail($this->record);
    }

    public function getTitle(): string
    {
        return 'Jadwal ' . $this->formatHari($this->hari) . ' - ' . ($this->kelas->nama ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(JadwalResource::getUrl('view', [
                    'record' => $this->kelas,
                ])),

            CreateAction::make()
                ->label('Tambah Jadwal')
                ->modalHeading('Tambah Jadwal ' . $this->formatHari($this->hari))
                ->schema($this->getFormSchema())
                ->createAnother(false)
                ->modalSubmitActionLabel('Simpan')
                ->modalCancelActionLabel('Batal')
                ->using(function (array $data): Jadwal {
                    $data['kelas_id'] = $this->kelas->id;
                    $data['hari'] = $this->hari;

                    return Jadwal::create($data);
                }),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('jam_ke')
                ->label('Jam Ke')
                ->required()
                ->numeric()
                ->minValue(1)
                ->maxValue(20)
                ->rule(function ($get, $record) {
                    $kelasId = $this->kelas->id;
                    $hari = $this->hari;

                    return (new Unique('jadwals', 'jam_ke'))
                        ->where(function ($query) use ($kelasId, $hari) {
                            return $query
                                ->where('kelas_id', $kelasId)
                                ->where('hari', $hari);
                        })
                        ->ignore($record);
                })
                ->validationMessages([
                    'unique' => 'Jam ke untuk hari ini sudah dipakai di kelas ini.',
                ]),

            Select::make('mapel_id')
                ->label('Mata Pelajaran')
                ->required()
                ->searchable()
                ->preload()
                ->native(false)
                ->options(
                    Mapel::query()
                        ->where('aktif', true)
                        ->orderBy('nama')
                        ->pluck('nama', 'id')
                        ->toArray()
                ),

            Select::make('guru_id')
                ->label('Guru')
                ->required()
                ->searchable()
                ->preload()
                ->native(false)
                ->options(
                    User::query()
                        ->where('role', 'guru')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                ),

            DatePicker::make('berlaku_dari')
                ->label('Berlaku Dari')
                ->native(false)
                ->displayFormat('d/m/Y'),

            DatePicker::make('berlaku_sampai')
                ->label('Berlaku Sampai')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->rule('after_or_equal:berlaku_dari')
                ->validationMessages([
                    'after_or_equal' => 'Berlaku sampai harus sama atau setelah berlaku dari.',
                ]),

            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->recordAction(null)
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('jam_ke')
                    ->label('Jam Ke')
                    ->sortable(),

                TextColumn::make('mapel.nama')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('guru.name')
                    ->label('Guru')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('berlaku_dari')
                    ->label('Berlaku Dari')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('berlaku_sampai')
                    ->label('Berlaku Sampai')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->schema($this->getFormSchema())
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->using(function (Jadwal $record, array $data): Jadwal {
                        $oldValues = [
                            'kelas_id' => $record->kelas_id,
                            'mapel_id' => $record->mapel_id,
                            'guru_id' => $record->guru_id,
                            'hari' => $record->hari,
                            'jam_ke' => $record->jam_ke,
                        ];

                        $data['kelas_id'] = $this->kelas->id;
                        $data['hari'] = $this->hari;

                        $willResetPresensi =
                            (int) $oldValues['kelas_id'] !== (int) $data['kelas_id'] ||
                            (int) $oldValues['mapel_id'] !== (int) $data['mapel_id'] ||
                            (int) $oldValues['guru_id'] !== (int) $data['guru_id'] ||
                            (string) $oldValues['hari'] !== (string) $data['hari'] ||
                            (int) $oldValues['jam_ke'] !== (int) $data['jam_ke'];

                        if ($willResetPresensi) {
                            $sessionIds = PresensiSesi::query()
                                ->where('jadwal_id', $record->id)
                                ->pluck('id');

                            if ($sessionIds->isNotEmpty()) {
                                PresensiDetail::query()
                                    ->whereIn('presensi_sesi_id', $sessionIds)
                                    ->delete();
                            }

                            PresensiSesi::query()
                                ->where('jadwal_id', $record->id)
                                ->delete();
                        }

                        $record->update($data);

                        return $record;
                    }),

                Action::make('hapus')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus jadwal?')
                    ->modalDescription('Jadwal pelajaran ini akan dihapus permanen.')
                    ->action(function (Jadwal $record): void {
                        $sessionIds = PresensiSesi::query()
                            ->where('jadwal_id', $record->id)
                            ->pluck('id');

                        if ($sessionIds->isNotEmpty()) {
                            PresensiDetail::query()
                                ->whereIn('presensi_sesi_id', $sessionIds)
                                ->delete();
                        }

                        PresensiSesi::query()
                            ->where('jadwal_id', $record->id)
                            ->delete();

                        $record->delete();

                        Notification::make()
                            ->title('Jadwal berhasil dihapus')
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
                        ->modalHeading('Hapus semua yang dipilih?')
                        ->modalDescription('Jadwal yang dipilih akan dihapus permanen.')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $sessionIds = PresensiSesi::query()
                                    ->where('jadwal_id', $record->id)
                                    ->pluck('id');

                                if ($sessionIds->isNotEmpty()) {
                                    PresensiDetail::query()
                                        ->whereIn('presensi_sesi_id', $sessionIds)
                                        ->delete();
                                }

                                PresensiSesi::query()
                                    ->where('jadwal_id', $record->id)
                                    ->delete();

                                $record->delete();
                            }

                            Notification::make()
                                ->title('Data terpilih berhasil dihapus')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return Jadwal::query()
            ->with(['mapel', 'guru'])
            ->where('kelas_id', $this->kelas->id)
            ->where('hari', $this->hari)
            ->orderBy('jam_ke');
    }

    protected function formatHari(?string $hari): string
    {
        return match ($hari) {
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            default => ucfirst((string) $hari),
        };
    }
}