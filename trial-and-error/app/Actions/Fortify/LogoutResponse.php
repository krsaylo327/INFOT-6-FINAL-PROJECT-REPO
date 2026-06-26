<?php

namespace App\Actions\Fortify;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Create an HTTP response that should be returned after the user logs out.
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('login');
    }
}
