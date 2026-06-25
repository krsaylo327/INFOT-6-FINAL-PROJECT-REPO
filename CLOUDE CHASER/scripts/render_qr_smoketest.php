<?php

/**
 * QR Tracing smoke test.
 *
 *   php artisan tinker scripts/render_qr_smoketest.php
 *
 * Renders the following views end-to-end against the *current database*
 * and writes them to storage/smoketest/ so they can be opened in a browser
 * without running a dev server or logging in.
 *
 *   1. trace/show.blade.php     → public trace page (redacted)
 *   2. travel_requests/print    → printable travel order with embedded QR
 *   3. QR endpoint (raw SVG)    → the QR SVG itself
 *
 * Picks the first approved TravelRequest if one exists, otherwise the first
 * record. Fails loudly if the table is empty.
 */

use App\Http\Controllers\TraceController;
use App\Models\TravelRequest;
use Illuminate\Support\Facades\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

$outDir = storage_path('smoketest');
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$tr = TravelRequest::where('status', 'approved')->first()
    ?? TravelRequest::orderByDesc('id')->first();

if (!$tr) {
    echo "❌ No travel requests in DB. Run `php artisan db:seed` first.\n";
    return;
}

$tr->load(['department', 'user', 'approvals.approver', 'assigner']);

echo "🎯 Using request: {$tr->request_no} · status={$tr->status} · {$tr->destination}\n";
echo "   Traveler: {$tr->user->name}\n";
echo "-----------------------------------------------------\n";

/* ---------- 1. Public trace page (redacted) ---------- */

$approvals = $tr->approvals->map(fn ($a) => [
    'level'  => $a->level,
    'action' => $a->action,
    'acted'  => $a->acted_at?->format('M d, Y'),
]);

$payload = [
    'request_no'        => $tr->request_no,
    'status'            => $tr->status,
    'destination'       => $tr->destination,
    'date_from'         => $tr->date_from?->format('M d, Y'),
    'date_to'           => $tr->date_to?->format('M d, Y'),
    'traveler_initials' => collect(preg_split('/\s+/', $tr->user->name))
        ->filter()
        ->map(fn ($p) => strtoupper(substr($p, 0, 1)))
        ->pipe(fn ($c) => $c->first() . ($c->count() > 1 ? $c->last() : '')),
    'approvals'         => $approvals,
    'progress'          => $approvals->count()
        ? "{$approvals->where('action','approved')->count()} of {$approvals->count()} levels approved"
        : 'No approval chain',
    'is_fully_approved' => $tr->status === 'approved',
    'is_terminated'     => in_array($tr->status, ['rejected', 'declined'], true),
    'scanned_at'        => now()->format('M d, Y h:i A'),
];

$traceHtml = View::make('trace.show', [
    'data'          => $payload,
    'travelRequest' => $tr,
])->render();

file_put_contents("$outDir/trace_show.html", $traceHtml);
echo "✅ trace/show.blade.php      → storage/smoketest/trace_show.html  (" . number_format(strlen($traceHtml)) . " bytes)\n";

// Sanity checks for redaction
$redactionChecks = [
    'Initials visible (not full name)' => str_contains($traceHtml, $payload['traveler_initials']) && !str_contains($traceHtml, $tr->user->name),
    'Purpose hidden'                   => !str_contains($traceHtml, $tr->purpose),
    'Cost hidden'                      => !str_contains($traceHtml, number_format($tr->estimated_cost, 2)) && !str_contains($traceHtml, (string) $tr->estimated_cost),
    'Status rendered'                  => str_contains($traceHtml, ucfirst($tr->status)) || str_contains($traceHtml, $tr->status),
    'Request_no rendered'              => str_contains($traceHtml, $tr->request_no),
    'noindex meta present'             => str_contains($traceHtml, 'noindex'),
];
foreach ($redactionChecks as $label => $ok) {
    echo "   " . ($ok ? '✔' : '✖') . "  $label\n";
}

/* ---------- 2. Printable travel order ---------- */

$printHtml = View::make('travel_requests.print', [
    'travelRequest' => $tr,
])->render();

file_put_contents("$outDir/print_travel_order.html", $printHtml);
echo "\n✅ travel_requests/print     → storage/smoketest/print_travel_order.html  (" . number_format(strlen($printHtml)) . " bytes)\n";

$printChecks = [
    'Contains "Official Travel Order"' => str_contains($printHtml, 'Official Travel Order'),
    'Traveler full name visible'       => str_contains($printHtml, $tr->user->name),
    'Destination visible'              => str_contains($printHtml, $tr->destination),
    'QR <img> src → travel-requests.qr route' => str_contains($printHtml, route('travel-requests.qr', $tr)),
    '@media print CSS block'           => str_contains($printHtml, '@media print'),
    'Approval table rendered'          => str_contains($printHtml, 'Approval Chain'),
];
foreach ($printChecks as $label => $ok) {
    echo "   " . ($ok ? '✔' : '✖') . "  $label\n";
}

/* ---------- 3. QR SVG (raw) ---------- */

$signedUrl = TraceController::signedTraceUrl($tr);
$svg = QrCode::format('svg')->size(220)->margin(1)->errorCorrection('M')->generate($signedUrl);
file_put_contents("$outDir/qr.svg", (string) $svg);
echo "\n✅ QR SVG (raw)              → storage/smoketest/qr.svg  (" . number_format(strlen((string) $svg)) . " bytes)\n";
echo "   Encoded URL: $signedUrl\n";

$svgChecks = [
    'Is SVG'          => str_starts_with((string) $svg, '<?xml') || str_contains((string) $svg, '<svg'),
    'Non-trivial size (>500b)' => strlen((string) $svg) > 500,
];
foreach ($svgChecks as $label => $ok) {
    echo "   " . ($ok ? '✔' : '✖') . "  $label\n";
}

echo "\n🎉 All three artifacts rendered. Open them in your browser:\n";
echo "   file:///" . str_replace('\\', '/', realpath("$outDir/trace_show.html")) . "\n";
echo "   file:///" . str_replace('\\', '/', realpath("$outDir/print_travel_order.html")) . "\n";
echo "   file:///" . str_replace('\\', '/', realpath("$outDir/qr.svg")) . "\n";
