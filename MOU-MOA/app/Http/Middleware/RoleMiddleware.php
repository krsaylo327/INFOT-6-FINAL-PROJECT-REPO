<?php

namespace App\Http\Middleware;

use App\Support\AgreementWorkflowMap;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {

        if (! auth()->check()) {
            abort(403);
        }

        $user = auth()->user();
        $normalizedRole = AgreementWorkflowMap::normalizeRole($user->role ?? '');
        $normalizedRoles = array_map(
            fn (string $role): string => AgreementWorkflowMap::normalizeRole($role),
            $roles
        );

        if (! in_array($normalizedRole, $normalizedRoles, true)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
