<?php

namespace App\Http\Middleware;

use App\Models\Notification;
use App\Support\AgreementWorkflowMap;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [

            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'role' => $request->user()->role,
                        'role_normalized' => AgreementWorkflowMap::normalizeRole($request->user()->role ?? ''),
                        'organization_id' => $request->user()->organization_id,
                        'coordinator_stage' => $request->user()->coordinator_stage,
                        'notifications' => Notification::where('user_id', $request->user()->id)
                            ->latest()
                            ->take(10)
                            ->get(),
                    ]
                    : null,
            ],

            'showAgreementsNav' => function () use ($request) {
                $role = $request->user()?->role ?? '';
                $roleNormalized = strtolower(trim(preg_replace('/\s+/', '_', $role)));

                return ! in_array($roleNormalized, ['system_admin', 'authorized_personnel']);
            },

        ]);
    }
}
