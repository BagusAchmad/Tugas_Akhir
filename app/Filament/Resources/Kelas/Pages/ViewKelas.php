<?php

namespace App\Filament\Resources\Kelas\Pages;

use App\Filament\Resources\Kelas\KelasResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
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
            ActionGroup::make([
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
                            'is_active' => $data['is_active'] ?? true,
                            'keterangan_nonaktif' => !($data['is_active'] ?? true) ? ($data['keterangan_nonaktif'] ?? null) : null,
                        ]);
                    }),

                Action::make('downloadTemplate')
                    ->label('Download Template Siswa')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function () {
                        $csvData = "# PETUNJUK PENGISIAN:\n";
                        $csvData .= "# Jika NIS diawali angka 0 (NOL), ketik tanda kutip satu di depannya. Contoh: '0110222555\n";
                        $csvData .= "No,Nama Siswa,NIS\n";
                        
                        return response()->streamDownload(function () use ($csvData) {
                            echo $csvData;
                        }, 'template_siswa.csv', [
                            'Content-Type' => 'text/csv',
                        ]);
                    }),

                Action::make('importSiswa')
                    ->label('Import Siswa')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        FileUpload::make('file')
                            ->label('Upload File Template (CSV)')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $filePath = Storage::disk('public')->path($data['file']);

                        if (!file_exists($filePath)) {
                            // Fallback to default disk if not public
                            $filePath = Storage::path($data['file']);
                        }

                        if (!file_exists($filePath) || !is_readable($filePath)) {
                            Notification::make()
                                ->title('File tidak dapat dibaca')
                                ->danger()
                                ->send();
                            return;
                        }

                        $handle = fopen($filePath, 'r');
                        
                        $countSuccess = 0;
                        $countSkipped = 0;

                        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                            if (count($row) < 3) {
                                continue;
                            }

                            $col1 = trim($row[0]);
                            
                            // Skip instructions, headers, or empty rows
                            if (str_starts_with($col1, '#') || strtolower($col1) === 'no' || $col1 === '') {
                                continue;
                            }

                            $namaSiswa = trim($row[1]);
                            $nis = trim($row[2]);
                            
                            // Handle leading apostrophe from Excel natively
                            $nis = ltrim($nis, "'");

                            if (empty($namaSiswa) || empty($nis)) {
                                continue;
                            }

                            if (User::where('username', $nis)->exists()) {
                                $countSkipped++;
                                continue;
                            }

                            User::create([
                                'name' => $namaSiswa,
                                'nis' => $nis,
                                'kelas_id' => $this->record->id,
                                'role' => 'siswa',
                                'username' => $nis,
                                'password' => Hash::make($nis),
                            ]);
                            
                            $countSuccess++;
                        }

                        fclose($handle);
                        
                        try {
                            Storage::disk('public')->delete($data['file']);
                        } catch (\Exception $e) {}

                        Notification::make()
                            ->title('Import Selesai')
                            ->body("Berhasil: {$countSuccess} siswa. Dilewatkan (Duplikat/Kosong): {$countSkipped} baris.")
                            ->success()
                            ->send();
                    }),

                Action::make('exportSiswa')
                    ->label('Export Siswa')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $siswas = User::where('role', 'siswa')
                            ->where('kelas_id', $this->record->id)
                            ->orderBy('name', 'asc')
                            ->get();

                        $filename = 'export_siswa_' . strtolower(str_replace(' ', '_', $this->record->nama ?? 'kelas')) . '_' . date('Ymd_His') . '.csv';

                        return response()->streamDownload(function () use ($siswas) {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, ['No', 'Nama Siswa', 'NIS']);

                            $no = 1;
                            foreach ($siswas as $siswa) {
                                // Prevent Excel from stripping leading zeros by adding an apostrophe
                                $nisStr = "'" . $siswa->nis;
                                fputcsv($handle, [$no, $siswa->name, $nisStr]);
                                $no++;
                            }

                            fclose($handle);
                        }, $filename, [
                            'Content-Type' => 'text/csv',
                        ]);
                    }),
            ])
            ->label('Kelola Siswa')
            ->icon('heroicon-m-chevron-down')
            ->button(),
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

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true)
                ->live(),

            Select::make('keterangan_nonaktif')
                ->label('Keterangan Nonaktif')
                ->options([
                    'Lulus' => 'Lulus',
                    'Pindah Sekolah' => 'Pindah Sekolah',
                    'Mengundurkan Diri' => 'Mengundurkan Diri',
                    'Dikeluarkan' => 'Dikeluarkan',
                    'Lainnya' => 'Lainnya',
                ])
                ->hidden(fn (Get $get) => $get('is_active') === true)
                ->required(fn (Get $get) => $get('is_active') === false)
                ->dehydrateStateUsing(fn ($state, Get $get) => $get('is_active') === true ? null : $state),

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

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('keterangan_nonaktif')
                    ->label('Keterangan')
                    ->state(fn ($record) => $record->is_active ? '-' : ($record->keterangan_nonaktif ?? '-'))
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

                Action::make('pindahRombel')
                    ->label('Pindah Rombel')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Pindah Rombel Siswa')
                    ->modalDescription('Pilih rombel / kelas tujuan untuk memindahkan siswa ini.')
                    ->form([
                        \Filament\Forms\Components\Select::make('kelas_id')
                            ->label('Rombel Tujuan')
                            ->options(function () {
                                return \App\Models\Kelas::query()
                                    ->where('id', '!=', $this->record->id)
                                    ->orderBy('nama')
                                    ->pluck('nama', 'id');
                            })
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'kelas_id' => $data['kelas_id'],
                        ]);

                        Notification::make()
                            ->title('Siswa berhasil dipindahkan')
                            ->success()
                            ->send();
                    }),

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
                    BulkAction::make('pindahRombelMassal')
                        ->label('Pindah Rombel')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Pindah Rombel Terpilih')
                        ->modalDescription('Pilih rombel / kelas tujuan untuk memindahkan semua siswa yang dipilih.')
                        ->form([
                            \Filament\Forms\Components\Select::make('kelas_id')
                                ->label('Rombel Tujuan')
                                ->options(function () {
                                    return \App\Models\Kelas::query()
                                        ->where('id', '!=', $this->record->id)
                                        ->orderBy('nama')
                                        ->pluck('nama', 'id');
                                })
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'kelas_id' => $data['kelas_id'],
                                ]);
                            }

                            $count = $records->count();

                            Notification::make()
                                ->title("{$count} Siswa berhasil dipindahkan")
                                ->success()
                                ->send();
                        }),

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