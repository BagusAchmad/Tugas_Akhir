<?php

namespace App\Filament\Siswa\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SiswaWelcome extends Widget
{
    protected string $view = 'filament.siswa.pages.widgets.siswa-welcome';

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        return [
            'nama' => Auth::user()?->name ?? 'Siswa',
        ];
    }
}