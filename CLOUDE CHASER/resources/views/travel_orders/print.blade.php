<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Order {{ $travelOrder->to_number }} — {{ $travelOrder->traveler->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
            background: #fff;
        }

        .page {
            width: 8.5in;
            min-height: 11in;
            margin: 0 auto;
            padding: 1in 1.25in 1in 1.5in;
        }

        /* Header */
        .letterhead {
            text-align: center;
            margin-bottom: 0.4in;
            border-bottom: 3px double #8b0000;
            padding-bottom: 12pt;
        }
        .letterhead .republic {
            font-size: 10pt;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .letterhead .university {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #8b0000;
            margin: 4pt 0;
        }
        .letterhead .address {
            font-size: 10pt;
            color: #444;
        }
        .letterhead .office {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 6pt;
        }

        /* TO Number */
        .to-header {
            text-align: center;
            margin-bottom: 0.35in;
        }
        .to-title {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 0.15em;
            text-transform: uppercase;
        }
        .to-number {
            font-size: 13pt;
            font-weight: bold;
            color: #8b0000;
        }

        /* Body */
        .body-text {
            font-size: 11pt;
            line-height: 1.9;
            text-align: justify;
            margin-bottom: 0.2in;
            text-indent: 0.5in;
        }

        /* Details table */
        .details-table {
            width: 100%;
            margin: 0.2in 0 0.3in 0.5in;
            font-size: 11pt;
            line-height: 1.8;
        }
        .details-table td.label {
            width: 2.2in;
            font-weight: bold;
        }

        /* Signature area */
        .signatures {
            margin-top: 0.5in;
            display: flex;
            justify-content: space-between;
        }
        .sig-block {
            text-align: center;
            width: 45%;
        }
        .sig-line {
            border-bottom: 1px solid #000;
            margin-bottom: 4pt;
            height: 0.5in;
        }
        .sig-name {
            font-weight: bold;
            font-size: 11pt;
            text-transform: uppercase;
        }
        .sig-title {
            font-size: 10pt;
            color: #444;
            font-style: italic;
        }

        /* Issued info */
        .issued-info {
            margin-top: 0.35in;
            font-size: 10pt;
            color: #555;
        }

        @media print {
            body { background: white; }
            .page { margin: 0; padding: 0.9in 1.2in 0.9in 1.4in; }
            .no-print { display: none; }
        }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 8px;
        }
        .btn {
            padding: 8px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-family: Arial, sans-serif;
        }
        .btn-primary { background: #8b0000; color: white; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn btn-primary">🖨 Print Travel Order</button>
    <button onclick="window.close()" class="btn btn-secondary">Close</button>
</div>

<div class="page">

    {{-- Letterhead --}}
    <div class="letterhead">
        <p class="republic">Republic of the Philippines</p>
        <p class="university">University of Antique</p>
        <p class="address">Sibalom, Antique</p>
        <p class="office">Office of the University President</p>
    </div>

    {{-- TO Number --}}
    <div class="to-header">
        <p class="to-title">Travel Order</p>
        <p class="to-number">{{ $travelOrder->to_number }}</p>
    </div>

    {{-- Body --}}
    <p class="body-text">
        This is to authorize <strong>{{ $travelOrder->traveler->name }}</strong>@if($travelOrder->traveler->requested_position),
        <em>{{ $travelOrder->traveler->requested_position }}</em>@endif,
        of the <strong>{{ $travelOrder->department->name }}</strong>,
        University of Antique, to travel to
        <strong>{{ $travelOrder->venue }}, {{ $travelOrder->destination }}</strong>
        to attend the <strong>{{ $travelOrder->event_name }}</strong>
        on <strong>{{ $travelOrder->formattedDates() }}</strong>.
    </p>

    <p class="body-text">
        The purpose of this travel is to {{ lcfirst($travelOrder->purpose) }}
    </p>

    <table class="details-table">
        <tr>
            <td class="label">Travel Type:</td>
            <td>{{ ucfirst($travelOrder->type) }}
                ({{ $travelOrder->type === 'academic' ? 'VPAA Endorsement' : 'VP Research Endorsement' }})
            </td>
        </tr>
        <tr>
            <td class="label">Department:</td>
            <td>{{ $travelOrder->department->name }}</td>
        </tr>
        <tr>
            <td class="label">Date Issued:</td>
            <td>{{ $travelOrder->issued_at?->format('F j, Y') }}</td>
        </tr>
    </table>

    <p class="body-text">
        This Travel Order is issued upon the recommendation of the Dean of {{ $travelOrder->department->name }}
        and in accordance with applicable university policies and guidelines.
    </p>

    {{-- Signatures --}}
    @php
        $issueSig  = $travelOrder->issueSignature();
        $reviewSig = $travelOrder->endorsementLetter?->reviewSignature();
        $vpReviewer = $travelOrder->endorsementLetter?->reviewer;
    @endphp

    {{-- Row 1: Dean (Recommended by) and — only for endorsed travel — VP (Reviewed by) --}}
    <div class="signatures" style="{{ $travelOrder->endorsementLetter ? '' : 'justify-content: flex-start;' }}">
        <div class="sig-block" style="position: relative;">
            <p style="font-size: 10pt; margin-bottom: 0.35in; font-style: italic;">Recommended by:</p>
            @if($travelOrder->dean?->signature_path)
            <div style="position: absolute; bottom: calc(100% - 0.85in); left: 50%; transform: translateX(-50%);">
                <img src="{{ route('profile.signature.show', $travelOrder->dean) }}"
                     alt="Dean signature"
                     style="height: 0.55in; max-width: 2in; object-fit: contain; display: block; margin: 0 auto;">
            </div>
            @endif
            <div class="sig-line"></div>
            <p class="sig-name">{{ strtoupper($travelOrder->dean->name) }}</p>
            <p class="sig-title">{{ $travelOrder->dean->requested_position ?? 'Dean' }}</p>
            <p class="sig-title">{{ $travelOrder->department->name }}</p>
        </div>
        @if($travelOrder->endorsementLetter)
        <div class="sig-block" style="position: relative;">
            <p style="font-size: 10pt; margin-bottom: 0.35in; font-style: italic;">Reviewed by:</p>
            @if($reviewSig)
            <div style="position: absolute; bottom: calc(100% - 0.85in); left: 50%; transform: translateX(-50%);">
                <img src="{{ route('signatures.verify.image', $reviewSig->verification_code) }}"
                     alt="VP signature"
                     style="height: 0.55in; max-width: 2in; object-fit: contain; display: block; margin: 0 auto;">
            </div>
            @endif
            <div class="sig-line"></div>
            <p class="sig-name">{{ strtoupper($reviewSig?->signer_name_snapshot ?? ($vpReviewer?->name ?? '—')) }}</p>
            <p class="sig-title">{{ $reviewSig?->signer_position_snapshot ?? ($vpReviewer?->requested_position ?? ($travelOrder->endorsementLetter?->reviewerLabel() ?? 'Vice President')) }}</p>
            <p class="sig-title">University of Antique</p>
            @if($reviewSig)
            <p style="font-size: 8pt; color: #555; margin-top: 3pt; font-style: italic;">
                ✓ Digitally Signed · {{ $reviewSig->signed_at->format('F j, Y') }}
            </p>
            @endif
        </div>
        @endif
    </div>

    {{-- Row 2: President (Approved by), centered --}}
    <div style="margin-top: 0.5in; text-align: center;">
        <p style="font-size: 10pt; margin-bottom: 0.35in; font-style: italic;">Approved by:</p>
        <div style="display: inline-block; text-align: center; width: 45%; position: relative;">
            @if($issueSig)
            <div style="position: absolute; bottom: calc(100% - 0.05in); left: 50%; transform: translateX(-50%);">
                <img src="{{ route('signatures.verify.image', $issueSig->verification_code) }}"
                     alt="President signature"
                     style="height: 0.55in; max-width: 2in; object-fit: contain; display: block; margin: 0 auto;">
            </div>
            @endif
            <div class="sig-line"></div>
            <p class="sig-name">{{ strtoupper($issueSig?->signer_name_snapshot ?? ($travelOrder->issuer?->name ?? 'THE UNIVERSITY PRESIDENT')) }}</p>
            <p class="sig-title">{{ $issueSig?->signer_position_snapshot ?? 'University President' }}</p>
            <p class="sig-title">University of Antique</p>
            @if($issueSig)
            <p style="font-size: 8pt; color: #555; margin-top: 3pt; font-style: italic;">
                ✓ Digitally Signed · {{ $issueSig->signed_at->format('F j, Y') }}
            </p>
            @endif
        </div>
    </div>

    @if($issueSig)
    <div style="margin-top: 0.25in; display: flex; align-items: center; gap: 10pt; padding: 8pt 10pt; border: 1px solid #d1fae5; background: #f0fdf4; border-radius: 4pt;">
        <img src="{{ route('signatures.verify.qr', $issueSig->verification_code) }}"
             alt="Signature QR" style="width: 0.7in; height: 0.7in; shrink: 0;">
        <div style="font-size: 8.5pt; color: #333; line-height: 1.5;">
            <p style="font-weight: bold; color: #065f46; text-transform: uppercase; font-size: 8pt; letter-spacing: 0.04em;">
                ✓ Digitally Signed by University President
            </p>
            <p>{{ $issueSig->signer_name_snapshot }} · {{ $issueSig->signed_at->format('F j, Y, g:i A') }}</p>
            <p style="font-family: monospace; font-size: 7.5pt; color: #555; margin-top: 1pt;">
                Code: {{ $issueSig->verification_code }}
            </p>
            <p style="font-size: 7.5pt; color: #777; margin-top: 1pt;">
                Scan QR or visit {{ route('signatures.verify', $issueSig->verification_code) }} to verify
            </p>
        </div>
    </div>
    @else
    <div class="issued-info">
        <p>Issued by: {{ $travelOrder->issuer?->name ?? '—' }} &nbsp;|&nbsp; Date: {{ $travelOrder->issued_at?->format('F j, Y') }}</p>
    </div>
    @endif

    {{-- Checkpoint verification QR --}}
    @if($travelOrder->isIssued() || $travelOrder->isActive() || $travelOrder->isCompleted())
    <div style="margin-top: 0.4in; padding-top: 12pt; border-top: 1px dashed #999; display: flex; align-items: center; gap: 14pt;">
        <div style="border: 1px solid #999; padding: 5pt; background: white; shrink: 0;">
            <img src="{{ route('travel-orders.qr', $travelOrder) }}"
                 alt="Verification QR" style="width: 1.1in; height: 1.1in; display: block;">
        </div>
        <div style="font-size: 9pt; color: #444; line-height: 1.5;">
            <p style="font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: #8b0000; font-size: 9.5pt;">
                Checkpoint Verification
            </p>
            <p>Scan the QR code with any mobile device to verify the authenticity of this Travel Order.</p>
            <p style="margin-top: 2pt;">
                Verification URL: <span style="font-family: monospace; font-size: 8pt;">{{ url('/verify-travel-order') }}/...</span>
            </p>
            <p style="font-family: monospace; font-size: 8pt; color: #777; margin-top: 2pt;">
                TO #{{ $travelOrder->to_number }} · Issued {{ $travelOrder->issued_at?->format('Y-m-d') }}
            </p>
        </div>
    </div>
    @endif

</div>
</body>
</html>
