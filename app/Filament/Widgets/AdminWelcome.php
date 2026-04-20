<?php

namespace App\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AdminWelcome extends Widget
{
    protected string $view = 'filament.widgets.admin-welcome';

    protected int|string|array $columnSpan = 'full';

    protected static string|Heroicon|null $icon = Heroicon::OutlinedHome;

    public function getViewData(): array
    {
        return [
            'nama' => Auth::user()?->name ?? 'Admin',
        ];
    }
}