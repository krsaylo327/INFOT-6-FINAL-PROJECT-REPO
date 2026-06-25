<?php
/**
 * Throwaway script to seed a stale pending approval so the
 * `approvals:escalate` command has something to act on during smoke testing.
 *
 * Usage:  php artisan tinker scripts/seed_stale_approval.php
 * or:     php -r "require 'bootstrap/app.php'; ..." (too noisy)
 *
 * Easier: run this via `php artisan tinker --execute="@require 'scripts/seed_stale_approval.php';"`
 */

use App\Models\Approval;
use App\Models\TravelRequest;
use App\Models\User;
use App\Services\ApprovalChainService;

$traveler = User::where('role', 'traveler')->first();

if (!$traveler) {
    echo "No traveler user found. Run `php artisan db:seed` first.\n";
    return;
}

$tr = TravelRequest::create([
    'request_no'     => 'TR-ESC-' . now()->format('YmdHis'),
    'user_id'        => $traveler->id,
    'department_id'  => $traveler->department_id,
    'destination'    => 'Iloilo',
    'purpose'        => 'Escalation smoke test',
    'date_from'      => now()->addWeek(),
    'date_to'        => now()->addWeek()->addDay(),
    'estimated_cost' => 3000,
    'status'         => 'pending',
    'type'           => 'self',
    'submitted_at'   => now(),
]);

app(ApprovalChainService::class)->initialize($tr);

// Back-date the pending approval so --days=3 picks it up
Approval::where('travel_request_id', $tr->id)
    ->where('action', 'pending')
    ->update(['created_at' => now()->subDays(5)]);

echo "Seeded travel request #{$tr->id} ({$tr->request_no}) ";
echo "with a 5-day-old pending approval.\n";
