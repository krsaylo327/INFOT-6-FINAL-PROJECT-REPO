<?php
/**
 * Blade smoke test — render the travel_requests.show view for our seeded
 * stale TR #5 as the approver, confirming:
 *   - new assignment banner logic doesn't explode
 *   - audit-log partial renders with our approval.escalated entry
 *   - no Blade compile errors
 *
 * Usage:  php artisan tinker scripts/render_show_smoketest.php
 */

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

// Mimic what ShareErrorsFromSession middleware does in the HTTP stack
View::share('errors', new ViewErrorBag);

$tr = TravelRequest::with([
    'user.department',
    'department',
    'approvals.approver',
    'auditLogs',
    'assigner',
])->find(5);

if (!$tr) {
    echo "TR #5 not found. Run scripts/seed_stale_approval.php first.\n";
    return;
}

$approver = User::where('role', 'approver')->where('approver_level', 1)->first();
Auth::login($approver);

try {
    $html = View::make('travel_requests.show', [
        'travelRequest' => $tr,
        'logs'          => $tr->auditLogs()->with('user')->latest()->get(),
    ])->render();

    $size       = number_format(strlen($html) / 1024, 1);
    $hasAck     = str_contains($html, 'Acknowledge') ? 'YES' : 'no';
    $hasBan     = str_contains($html, 'This trip was assigned') ? 'YES' : 'no';
    $hasAuditH  = str_contains($html, 'Audit Log') ? 'YES' : 'no';
    $hasEscRow  = str_contains($html, 'Approval Escalated') ? 'YES' : 'no';
    $hasMeta    = str_contains($html, 'threshold_days') ? 'YES' : 'no';

    echo "Rendered OK — {$size} KB\n";
    echo "  (self request — banners NOT expected)\n";
    echo "  Acknowledge banner absent?       " . ($hasAck === 'no' ? 'OK' : 'UNEXPECTED') . "\n";
    echo "  Assignment banner absent?        " . ($hasBan === 'no' ? 'OK' : 'UNEXPECTED') . "\n";
    echo "  Audit Log heading rendered?      {$hasAuditH}\n";
    echo "  'Approval Escalated' row shown?  {$hasEscRow}\n";
    echo "  Metadata 'threshold_days' shown? {$hasMeta}\n";
} catch (\Throwable $e) {
    echo "RENDER FAILED: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
