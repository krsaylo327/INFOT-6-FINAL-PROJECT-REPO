<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

class TraceController extends Controller
{
    /**
     * Public traceability page — scanned by security/checkpoint staff.
     * Route is rate-limited and signed; no login required.
     */
    public function show(Request $request, string $requestNo): Response
    {
        // Signed-URL validation. If signature is missing / bad / tampered → 403.
        if (!$request->hasValidSignature()) {
            abort(403, 'This trace link is invalid or has been revoked.');
        }

        $travelRequest = TravelRequest::where('request_no', $requestNo)
            ->with(['approvals' => fn ($q) => $q->orderBy('level'), 'itinerary', 'liquidation'])
            ->firstOrFail();

        // Record the scan in the audit log. Non-blocking: failures here must
        // never break the page for a scanning guard.
        try {
            AuditLogger::log('request.traced', $travelRequest, [
                'ip'         => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'via'        => 'qr-scan',
            ], userId: null); // public — no actor
        } catch (\Throwable $e) {
            report($e);
        }

        // Build a redacted payload for public consumption.
        $approvals = $travelRequest->approvals->map(fn ($a) => [
            'level'  => $a->level,
            'action' => $a->action,
            'acted'  => $a->acted_at?->format('M d, Y'),
        ]);

        $totalLevels   = $approvals->count();
        $approvedCount = $approvals->where('action', 'approved')->count();

        $itin = $travelRequest->itinerary;
        $liq  = $travelRequest->liquidation;

        $payload = [
            'request_no'        => $travelRequest->request_no,
            'status'            => $travelRequest->status,
            'destination'       => $travelRequest->destination,
            'date_from'         => $travelRequest->date_from?->format('M d, Y'),
            'date_to'           => $travelRequest->date_to?->format('M d, Y'),
            'type'              => $travelRequest->type,
            'acknowledged_at'   => $travelRequest->acknowledged_at?->format('M d, Y'),
            'traveler_initials' => self::initials($travelRequest->user?->name ?? '—'),
            'approvals'         => $approvals,
            'progress'          => $totalLevels > 0
                ? "{$approvedCount} of {$totalLevels} levels approved"
                : 'No approval chain',
            'is_fully_approved' => $travelRequest->status === 'approved',
            'is_terminated'     => \in_array($travelRequest->status, ['rejected', 'declined'], true),
            'scanned_at'        => now()->format('M d, Y h:i A'),
            'itinerary'         => $itin ? [
                'status'          => $itin->status,
                'departure_place' => $itin->departure_place,
                'arrival_place'   => $itin->arrival_place,
                'departure_at'    => $itin->departure_at?->format('M d, Y h:i A'),
                'return_at'       => $itin->return_at?->format('M d, Y h:i A'),
                'transport_mode'  => $itin->transport_mode,
                'accommodation'   => $itin->accommodation,
            ] : null,
            'liquidation'       => $liq ? [
                'status'         => $liq->status,
                'total_claimed'  => $liq->total_claimed,
                'total_approved' => $liq->total_approved,
                'submitted_at'   => $liq->submitted_at?->format('M d, Y'),
                'approved_at'    => $liq->approved_at?->format('M d, Y'),
            ] : null,
        ];

        return response()
            ->view('trace.show', ['data' => $payload, 'travelRequest' => $travelRequest])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Inline QR SVG for a travel request. Auth-protected (embedded in show/print views).
     * Encodes the signed public trace URL.
     */
    public function qr(TravelRequest $travelRequest): Response
    {
        $url = self::signedTraceUrl($travelRequest);

        $svg = QrCode::format('svg')
            ->size(220)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($url);

        return response((string) $svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * Build the signed URL pointing to the public trace page for a given travel request.
     * Exposed statically so Blade views and tests can reuse it.
     */
    public static function signedTraceUrl(TravelRequest $travelRequest): string
    {
        return URL::signedRoute('trace.show', ['requestNo' => $travelRequest->request_no]);
    }

    private static function initials(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0][0] ?? '';
        $last  = (count($parts) > 1 ? $parts[count($parts) - 1][0] : '');
        return strtoupper($first . $last) ?: '—';
    }
}
