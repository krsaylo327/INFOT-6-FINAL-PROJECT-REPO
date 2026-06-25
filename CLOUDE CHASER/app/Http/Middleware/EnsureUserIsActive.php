<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && in_array($user->status, ['disabled', 'rejected'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $user->status === 'disabled'
                ? 'Your account has been disabled. Please contact the administrator.'
                : 'Your account registration was rejected. Please contact the administrator.';

            return redirect()->route('login')
                ->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
