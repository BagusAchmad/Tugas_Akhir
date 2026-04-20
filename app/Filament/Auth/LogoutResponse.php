<?php

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        return redirect('/login');
    }
}