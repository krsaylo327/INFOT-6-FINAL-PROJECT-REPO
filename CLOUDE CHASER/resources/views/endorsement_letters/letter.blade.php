<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Endorsement Letter — {{ $endorsementLetter->invitation->event_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
            background: #e5e7eb;
        }

        .page {
            width: 8.5in;
            min-height: 11in;
            margin: 0.5in auto;
            padding: 1in 1.25in 1in 1.5in;
            position: relative;
            background: #fff;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }

        /* Letterhead */
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
            font-size: 20pt;
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
        .letterhead .college {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 6pt;
            text-transform: uppercase;
        }
        .letterhead .dept {
            font-size: 10pt;
            color: #555;
            font-style: italic;
        }

        /* Date */
        .date-line {
            text-align: right;
            margin-bottom: 0.3in;
            font-size: 11pt;
        }

        /* Addressee */
        .addressee {
            margin-bottom: 0.25in;
            font-size: 11pt;
            line-height: 1.5;
        }
        .addressee .label {
            font-weight: bold;
            font-size: 11pt;
            text-transform: uppercase;
        }
        .addressee .thru {
            margin-top: 0.15in;
        }

        /* Subject */
        .subject {
            margin-bottom: 0.2in;
            font-size: 11pt;
            border-left: 4px solid #8b0000;
            padding: 6pt 10pt;
            background: #fdf2f2;
        }
        .subject strong { text-transform: uppercase; }

        /* Salutation */
        .salutation {
            margin-bottom: 0.2in;
            font-size: 11pt;
        }

        /* Body */
        .body-text {
            font-size: 11pt;
            line-height: 1.7;
            text-align: justify;
            margin-bottom: 0.15in;
            text-indent: 0.5in;
        }

        /* Lists */
        .staff-list {
            margin: 0.15in 0.5in;
            font-size: 11pt;
            line-height: 1.8;
        }
        .staff-list li {
            margin-bottom: 4pt;
        }

        /* Funding box */
        .funding-box {
            margin: 0.2in 0;
            padding: 10pt 14pt;
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 4pt;
            font-size: 11pt;
            line-height: 1.7;
        }
        .funding-box .label {
            display: inline-block;
            min-width: 1.4in;
            font-weight: bold;
            color: #555;
        }

        /* Closing */
        .closing {
            margin-top: 0.5in;
            font-size: 11pt;
        }
        .signature-block {
            margin-top: 0.6in;
            display: inline-block;
            position: relative;
        }
        .signature-image-block {
            position: absolute;
            top: -0.6in;
            left: 0;
        }
        .signature-image-block img {
            height: 0.7in;
            max-width: 2.4in;
            object-fit: contain;
        }
        .signatory-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11pt;
            border-top: 1px solid #000;
            padding-top: 2pt;
            min-width: 2.4in;
            display: inline-block;
        }
        .signatory-title {
            font-size: 10pt;
            color: #333;
            font-style: italic;
        }

        /* Signed badge */
        .signed-badge {
            display: inline-flex;
            align-items: center;
            gap: 4pt;
            padding: 3pt 8pt;
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
            border-radius: 4pt;
            font-size: 9pt;
            font-weight: bold;
            margin-left: 8pt;
            font-style: normal;
        }

        /* Approval/Review section */
        .review-section {
            margin-top: 0.5in;
            padding: 0.2in;
            border: 1px dashed #8b0000;
            background: #fdf6f6;
        }
        .review-section .review-title {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11pt;
            margin-bottom: 0.15in;
            color: #8b0000;
        }
        .review-signature {
            display: flex;
            align-items: end;
            gap: 0.3in;
        }
        .review-signature .sig-img {
            border: 1px solid #ccc;
            padding: 4pt;
            background: white;
        }
        .review-signature .sig-img img {
            height: 0.7in;
            max-width: 2in;
        }
        .review-signature .sig-meta {
            flex: 1;
        }
        .review-signature .sig-meta .name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11pt;
        }
        .review-signature .sig-meta .pos {
            font-size: 9.5pt;
            font-style: italic;
            color: #555;
        }
        .review-signature .sig-meta .date {
            font-size: 9pt;
            color: #777;
            margin-top: 2pt;
        }
        .review-signature .sig-qr {
            text-align: center;
            shrink: 0;
        }
        .review-signature .sig-qr img {
            width: 0.9in;
            height: 0.9in;
        }
        .review-signature .sig-qr p {
            font-size: 8pt;
            color: #555;
            margin-top: 3pt;
        }

        /* Footer */
        .footer {
            margin-top: 0.4in;
            padding-top: 10pt;
            border-top: 1px solid #ddd;
            font-size: 9pt;
            color: #888;
            display: flex;
            justify-content: space-between;
        }

        /* Action bar */
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 8px;
            z-index: 100;
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

        @media print {
            body { background: white; }
            .page { margin: 0; padding: 0.9in 1.2in 0.9in 1.4in; box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn btn-primary">🖨 Print Letter</button>
    <button onclick="window.close()" class="btn btn-secondary">Close</button>
</div>

@php
    $reviewSig = $endorsementLetter->reviewSignature();
    $allTravelers = $endorsementLetter->staff;
    $invitation = $endorsementLetter->invitation;
    $dean = $endorsementLetter->dean;
    $isResearch = $endorsementLetter->category === 'research';
@endphp

<div class="page">

    {{-- Letterhead --}}
    <div class="letterhead">
        <p class="republic">Republic of the Philippines</p>
        <p class="university">University of Antique</p>
        <p class="address">Sibalom, Antique 5713 · Philippines</p>
        <p class="college">{{ $dean->department->name ?? '' }}</p>
        @if($dean->department && $dean->department->abbreviation && $dean->department->abbreviation !== $dean->department->name)
            <p class="dept">({{ $dean->department->abbreviation }})</p>
        @endif
    </div>

    {{-- Date --}}
    <p class="date-line">{{ ($endorsementLetter->submitted_at ?? $endorsementLetter->created_at)->format('F j, Y') }}</p>

    {{-- Addressee --}}
    <div class="addressee">
        @if($isResearch)
            <p class="label">{{ strtoupper('Dr. ' . ($endorsementLetter->reviewer->name ?? 'The Vice President for Research, Extension and Innovation')) }}</p>
            <p>Vice President for Research, Extension and Innovation</p>
        @else
            <p class="label">{{ strtoupper('Dr. ' . ($endorsementLetter->reviewer->name ?? 'The Vice President for Academic Affairs')) }}</p>
            <p>Vice President for Academic Affairs</p>
        @endif
        <p>University of Antique</p>
        <p>Sibalom, Antique</p>

        <div class="thru">
            <p><em>Thru:</em> &nbsp;&nbsp;The Office of the University President</p>
            <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;University of Antique</p>
        </div>
    </div>

    {{-- Subject --}}
    <p class="subject">
        <strong>Subject:</strong>
        Endorsement of {{ $allTravelers->count() }} {{ Str::plural('Staff', $allTravelers->count()) }}
        to attend <em>{{ $invitation->event_name }}</em>
    </p>

    {{-- Salutation --}}
    <p class="salutation">Dear Sir/Madam:</p>

    {{-- Body --}}
    <p class="body-text">
        With reference to the invitation forwarded by the Office of the University President for the
        <strong>{{ $invitation->event_name }}</strong> to be held on
        <strong>{{ $invitation->formattedDates() }}</strong> at
        {{ $invitation->venue ?? $invitation->destination }},
        I have the honor to respectfully endorse the following
        {{ Str::plural('faculty member', $allTravelers->count()) }} from the
        <strong>{{ $dean->department->name ?? '' }}</strong>
        to attend on behalf of the {{ $dean->department->name ?? 'College' }}:
    </p>

    <ol class="staff-list">
        @foreach($allTravelers as $member)
            @php
                $pos  = $member->pivot->position ?? null;
                $role = $member->pivot->role_in_event ?? null;
            @endphp
            <li>
                <strong>{{ $member->name }}</strong>@if($pos), {{ $pos }}@endif @if($role)— <em>{{ $role }}</em>@endif
            </li>
        @endforeach
    </ol>

    <p class="body-text">
        <strong>Reason for Endorsement:</strong>
        {{ $endorsementLetter->reason_for_endorsing }}
    </p>

    <p class="body-text">
        <strong>Justification:</strong>
        {{ $endorsementLetter->justification }}
    </p>

    <p class="body-text">
        <strong>Expected Outcomes:</strong>
        {{ $endorsementLetter->expected_outcomes }}
    </p>


    <p class="body-text">
        In view of the foregoing, the endorsed {{ Str::plural('staff', $allTravelers->count()) }}
        {{ $allTravelers->count() > 1 ? 'are' : 'is' }} ready to represent the
        {{ $dean->department->name ?? 'College' }} and the University of Antique in this important activity.
        Your favorable action on this endorsement is highly appreciated.
    </p>

    <p class="body-text">Thank you very much.</p>

    {{-- Closing --}}
    <div class="closing">
        <p>Respectfully yours,</p>

        <div class="signature-block">
            @if($dean->hasSignature())
                <div class="signature-image-block">
                    <img src="{{ url('users/' . $dean->id . '/signature.png') }}" alt="Signature">
                </div>
            @endif
            <p class="signatory-name">{{ strtoupper($dean->name) }}</p>
            <p class="signatory-title">{{ $dean->requested_position ?? ($dean->department && $dean->department->abbreviation === 'PRES' ? 'University President' : 'Dean') }}</p>
            <p class="signatory-title">{{ $dean->department->name ?? '' }}</p>
        </div>
    </div>

    {{-- Review/Approval section (if reviewed) --}}
    @if($reviewSig && $endorsementLetter->isApproved())
    <div class="review-section">
        <p class="review-title">✓ Approved by {{ $isResearch ? 'VPREI' : 'VPAA' }}</p>

        <div class="review-signature">
            <div class="sig-img">
                <img src="{{ route('signatures.verify.image', $reviewSig->verification_code) }}" alt="Approver signature">
            </div>
            <div class="sig-meta">
                <p class="name">{{ $reviewSig->signer_name_snapshot }}
                    <span class="signed-badge">✓ Digitally Signed</span>
                </p>
                <p class="pos">{{ $reviewSig->signer_position_snapshot ?? '' }}</p>
                <p class="date">Signed: {{ $reviewSig->signed_at->format('F j, Y, g:i A') }}</p>
                @if($reviewSig->decision_remarks)
                    <p style="font-size: 9.5pt; margin-top: 4pt;"><strong>Remarks:</strong> <em>{{ $reviewSig->decision_remarks }}</em></p>
                @endif
            </div>
            <div class="sig-qr">
                <img src="{{ route('signatures.verify.qr', $reviewSig->verification_code) }}" alt="Verification QR">
                <p>Scan to verify</p>
                <p style="font-family: monospace; font-size: 7.5pt; color: #444;">{{ $reviewSig->verification_code }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div>UA-TRaMP · Endorsement Letter #{{ $endorsementLetter->id }}</div>
        <div>{{ $isResearch ? 'Research' : 'Academic' }} Category</div>
    </div>

</div>

</body>
</html>
