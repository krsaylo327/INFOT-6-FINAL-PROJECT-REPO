<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SignatureVerificationController extends Controller
{
    /**
     * Public verification page. Anyone with the code can verify the signature's authenticity.
     */
    public function show(string $code): View
    {
        $signature = Signature::with(['signer.department', 'signable'])
            ->where('verification_code', $code)
            ->first();

        return view('signatures.verify', compact('signature', 'code'));
    }

    /**
     * Stream the signature image used at the time of signing (snapshot).
     * No auth required — same scope as the verification page.
     */
    public function image(string $code)
    {
        $signature = Signature::where('verification_code', $code)->firstOrFail();

        if (!Storage::disk('private')->exists($signature->signature_image_path)) {
            abort(404);
        }

        return Storage::disk('private')->response($signature->signature_image_path);
    }

    /**
     * Generate a QR code (SVG) that encodes the public verification URL.
     * Anyone scanning it lands on the verification page — no auth required.
     */
    public function qr(string $code): Response
    {
        // Confirm the code exists; otherwise we'd be encoding a 404 link.
        Signature::where('verification_code', $code)->firstOrFail();

        $url = route('signatures.verify', $code);

        $svg = QrCode::format('svg')
            ->size(220)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($url);

        return response((string) $svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
