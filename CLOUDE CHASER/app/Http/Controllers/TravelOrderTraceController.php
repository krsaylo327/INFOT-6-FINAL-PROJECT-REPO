<?php

namespace App\Http\Controllers;

use App\Models\TravelOrder;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TravelOrderTraceController extends Controller
{
    /**
     * Public checkpoint verification page for a Travel Order — scanned by
     * checkpoint staff, hotels, conference organizers, etc.
     * Signed URL, rate-limited, no auth required.
     */
    public function show(Request $request, string $toNumber): Response
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'This trace link is invalid or has been revoked.');
        }

        $travelOrder = TravelOrder::where('to_number', $toNumber)
            ->with(['traveler', 'travelers', 'dean', 'department', 'issuer'])
            ->firstOrFail();

        // Log the scan as an audit event (non-blocking).
        try {
            AuditLogger::log('travel_order.traced', $travelOrder, [
                'ip'         => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'via'        => 'qr-scan',
            ], userId: null);
        } catch (\Throwable $e) {
            report($e);
        }

        $allTravelers = $travelOrder->travelers->count()
            ? $travelOrder->travelers
            : collect([$travelOrder->traveler])->filter();

        $payload = [
            'to_number'       => $travelOrder->to_number,
            'status'          => $travelOrder->status,
            'event_name'      => $travelOrder->event_name,
            'destination'     => $travelOrder->destination,
            'venue'           => $travelOrder->venue,
            'date_from'       => $travelOrder->date_from?->format('M d, Y'),
            'date_to'         => $travelOrder->date_to?->format('M d, Y'),
            'formatted_dates' => $travelOrder->formattedDates(),
            'type'            => $travelOrder->type,
            'department'      => $travelOrder->department?->name,
            'travelers'       => $allTravelers->map(fn ($t) => [
                'name'     => $t->name,
                'position' => $t->requested_position,
            ])->values(),
            'has_students'    => (bool) $travelOrder->has_students,
            'student_count'   => $travelOrder->student_count,
            'dean'            => $travelOrder->dean?->name,
            'issued_by'       => $travelOrder->issuer?->name,
            'issued_at'       => $travelOrder->issued_at?->format('M d, Y'),
            'is_valid'        => in_array($travelOrder->status, ['issued', 'active', 'completed'], true),
            'scanned_at'      => now()->format('M d, Y h:i A'),
        ];

        return response()
            ->view('travel_orders.trace', ['data' => $payload, 'travelOrder' => $travelOrder])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Inline QR SVG for a travel order. Encodes the signed public trace URL.
     */
    public function qr(TravelOrder $travelOrder): Response
    {
        if (!$travelOrder->to_number) {
            abort(404, 'Travel Order has not been issued a TO number yet.');
        }

        $url = self::signedTraceUrl($travelOrder);

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

    public static function signedTraceUrl(TravelOrder $travelOrder): string
    {
        return URL::signedRoute('travel-orders.trace', ['toNumber' => $travelOrder->to_number]);
    }
}
