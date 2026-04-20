<?php

namespace App\Providers\Filament;

use App\Filament\Siswa\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SiswaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('siswa')
            ->path('siswa')
            ->brandName('SMK Al Hafidz')
            ->favicon(asset('images/logo.png'))
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Purple,
            ])
            ->login(false)
            ->authGuard('web')
            ->discoverResources(
                in: app_path('Filament/Siswa/Resources'),
                for: 'App\\Filament\\Siswa\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Siswa/Pages'),
                for: 'App\\Filament\\Siswa\\Pages'
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Siswa/Widgets'),
                for: 'App\\Filament\\Siswa\\Widgets'
            )
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}