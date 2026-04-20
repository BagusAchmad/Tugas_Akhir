<?php

namespace App\Providers\Filament;

use App\Filament\Guru\Pages\Dashboard;
use App\Filament\Guru\Pages\RekapPerSiswa;
use App\Filament\Guru\Pages\RekapWaliKelas;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class GuruPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('guru')
            ->path('guru')
            ->brandName('SMK Al Hafidz')
            ->favicon(asset('images/logo.png'))
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Purple,
            ])
            ->login(false)
            ->authGuard('web')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->discoverResources(in: app_path('Filament/Guru/Resources'), for: 'App\\Filament\\Guru\\Resources')
            ->discoverPages(in: app_path('Filament/Guru/Pages'), for: 'App\\Filament\\Guru\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationItems([
                NavigationItem::make('Rekap Pelajaran')
                    ->group('Rekap Kelas')
                    ->sort(30)
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->url(fn (): string => RekapWaliKelas::getUrl())
                    ->visible(fn (): bool => Auth::check()
                        && Auth::user()?->role === 'guru'
                        && filled(Auth::user()?->wali_kelas_id))
                    ->isActiveWhen(fn (): bool => request()->routeIs(
                        'filament.guru.pages.rekap-wali-kelas',
                        'filament.guru.pages.rekap-wali-kelas-detail.{hari}',
                        'filament.guru.pages.rekap-wali-kelas-pertemuan.{jadwal}',
                        'filament.guru.pages.rekap-wali-kelas-siswa.{record}',
                    )),

                NavigationItem::make('Rekap Siswa')
                    ->group('Rekap Kelas')
                    ->sort(31)
                    ->icon(Heroicon::OutlinedUsers)
                    ->url(fn (): string => RekapPerSiswa::getUrl())
                    ->visible(fn (): bool => Auth::check()
                        && Auth::user()?->role === 'guru'
                        && filled(Auth::user()?->wali_kelas_id))
                    ->isActiveWhen(fn (): bool => request()->routeIs(
                        'filament.guru.pages.rekap-per-siswa',
                        'filament.guru.pages.rekap-per-siswa-detail.{siswa}',
                        'filament.guru.pages.rekap-per-siswa-hari.{siswa}.{hari}',
                    )),
            ])
            ->discoverWidgets(in: app_path('Filament/Guru/Widgets'), for: 'App\\Filament\\Guru\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}