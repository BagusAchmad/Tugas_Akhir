<?php

namespace App\Filament\Guru\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class GuruWelcome extends Widget
{
    protected string $view = 'filament.guru.pages.widgets.guru-welcome';

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        return [
            'nama' => Auth::user()?->name ?? 'Guru',
        ];
    }
}