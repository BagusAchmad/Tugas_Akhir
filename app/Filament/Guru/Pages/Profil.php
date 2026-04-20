<?php

namespace App\Filament\Guru\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Profil extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Profil';

    protected static ?string $title = 'Profil';

    protected static string|\UnitEnum|null $navigationGroup = 'Akun';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.guru.pages.profil';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()?->role === 'guru';
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'guru', 403);

        $this->form->fill([
            'name' => $user->name,
            'username' => $user->username ?? $user->email,
            'password' => null,
            'password_confirmation' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Akun')
                    ->description('Kelola data akun guru yang sedang login.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Akun')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('username')
                                    ->label('Username / Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(
                                        table: 'users',
                                        column: 'username',
                                        ignorable: Auth::user(),
                                    ),
                            ]),
                    ]),

                Section::make('Ubah Password')
                    ->description('Password lama tidak dapat ditampilkan. Isi password baru jika ingin mengganti password.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('password')
                                    ->label('Password Baru')
                                    ->password()
                                    ->revealable()
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->same('password_confirmation')
                                    ->dehydrated(fn ($state) => filled($state)),

                                TextInput::make('password_confirmation')
                                    ->label('Konfirmasi Password Baru')
                                    ->password()
                                    ->revealable()
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->dehydrated(false),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function simpan(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user && $user->role === 'guru', 403);

        $data = $this->form->getState();

        $usernameLama = $user->username ?? $user->email;
        $usernameBerubah = $usernameLama !== $data['username'];
        $passwordBerubah = ! empty($data['password']);

        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->email = $data['username'];

        if ($passwordBerubah) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if ($usernameBerubah || $passwordBerubah) {
            Auth::logout();

            request()->session()->invalidate();
            request()->session()->regenerateToken();

            $this->redirect('/login', navigate: true);
            return;
        }

        $this->form->fill([
            'name' => $user->name,
            'username' => $user->username ?? $user->email,
            'password' => null,
            'password_confirmation' => null,
        ]);

        Notification::make()
            ->title('Profil guru berhasil diperbarui')
            ->success()
            ->send();
    }
}