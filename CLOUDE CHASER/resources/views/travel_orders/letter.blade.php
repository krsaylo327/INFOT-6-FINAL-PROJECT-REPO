<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Endorsement Letter — {{ $travelOrder->traveler->name }}</title>
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
            position: relative;
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
        .letterhead .college {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 6pt;
        }
        .letterhead .dept {
            font-size: 10pt;
            color: #555;
        }

        /* Date */
        .date-line {
            text-align: right;
            margin-bottom: 0.3in;
            font-size: 11pt;
        }

        /* Addressee block */
        .addressee {
            margin-bottom: 0.25in;
            font-size: 11pt;
        }
        .addressee .label {
            font-weight: bold;
            font-size: 11pt;
        }
        .addressee .thru {
            margin-top: 0.15in;
        }

        /* Salutation */
        .salutation {
            margin-bottom: 0.2in;
            font-size: 11pt;
        }

        /* Body */
        .body-text {
            font-size: 11pt;
            line-height: 1.8;
            text-align: justify;
            margin-bottom: 0.15in;
            text-indent: 0.5in;
        }

        /* Closing */
        .closing {
            margin-top: 0.4in;
            font-size: 11pt;
        }
        .signature-block {
            margin-top: 0.6in;
        }
        .signatory-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11pt;
        }
        .signatory-title {
            font-size: 10.5pt;
            color: #333;
            font-style: italic;
        }

        @media print {
            body { background: white; }
            .page { margin: 0; padding: 0.9in 1.2in 0.9in 1.4in; }
            .no-print { display: none; }
        }

        /* Print button */
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
    <button onclick="window.print()" class="btn btn-primary">🖨 Print Letter</button>
    <button onclick="window.close()" class="btn btn-secondary">Close</button>
</div>

<div class="page">

    {{-- Letterhead --}}
    <div class="letterhead">
        <p class="republic">Republic of the Philippines</p>
        <p class="university">University of Antique</p>
        <p class="address">Sibalom, Antique</p>
        <p class="college">{{ $travelOrder->department->name }}</p>
    </div>

    {{-- Date --}}
    <p class="date-line">{{ now()->format('F j, Y') }}</p>

    {{-- Addressee --}}
    <div class="addressee">
        <p class="label">THE UNIVERSITY PRESIDENT</p>
        <p>University of Antique</p>
        <p>Sibalom, Antique</p>

        <div class="thru">
            @if($travelOrder->type === 'research')
                <p><em>Thru:</em> &nbsp;&nbsp;The Director, Research Unit</p>
                <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;University of Antique</p>
                <br>
                <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The {{ $travelOrder->vpLabel() }}</p>
                <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Office of the {{ $travelOrder->vpLabel() }}</p>
            @else
                <p><em>Thru:</em> &nbsp;&nbsp;The {{ $travelOrder->vpLabel() }}</p>
                <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Office of the {{ $travelOrder->vpLabel() }}</p>
            @endif
        </div>
    </div>

    {{-- Salutation --}}
    <p class="salutation">Dear Sir/Madam:</p>

    {{-- Body --}}
    @if($travelOrder->isPersonal())
    <p class="body-text">
        I have the honor to respectfully request your permission to allow me to attend the
        <strong>{{ $travelOrder->event_name }}</strong>
        on <strong>{{ $travelOrder->formattedDates() }}</strong>
        at {{ $travelOrder->venue }}, {{ $travelOrder->destination }}.
        I am a {{ $travelOrder->traveler->requested_position ?? 'faculty member' }}
        of the {{ $travelOrder->department->name }}, University of Antique.
    </p>
    @else
    @php $allTravelers = $travelOrder->travelers->count() ? $travelOrder->travelers : collect([$travelOrder->traveler])->filter(); @endphp
    @if($allTravelers->count() === 1)
    <p class="body-text">
        I have the honor to respectfully request your permission to allow
        <strong>{{ $allTravelers->first()->name }}</strong>@if($allTravelers->first()->requested_position),
        {{ $allTravelers->first()->requested_position }}@endif,
        of the {{ $travelOrder->department->name }}, University of Antique, to attend the
        <strong>{{ $travelOrder->event_name }}</strong>
        on <strong>{{ $travelOrder->formattedDates() }}</strong>
        at {{ $travelOrder->venue }}, {{ $travelOrder->destination }}.
    </p>
    @else
    <p class="body-text">
        I have the honor to respectfully request your permission to allow the following
        faculty/staff members of the {{ $travelOrder->department->name }}, University of Antique,
        to attend the <strong>{{ $travelOrder->event_name }}</strong>
        on <strong>{{ $travelOrder->formattedDates() }}</strong>
        at {{ $travelOrder->venue }}, {{ $travelOrder->destination }}:
    </p>
    <ol style="margin-left: 1in; margin-bottom: 0.15in; font-size: 11pt; line-height: 1.8;">
        @foreach($allTravelers as $t)
        <li><strong>{{ $t->name }}</strong>@if($t->requested_position), {{ $t->requested_position }}@endif</li>
        @endforeach
    </ol>
    @endif
    @endif

    <p class="body-text">
        The purpose of this travel is to {{ lcfirst($travelOrder->purpose) }}
    </p>

    <p class="body-text">
        In view of the foregoing, your favorable action on this request will be highly appreciated.
    </p>

    {{-- Closing --}}
    <div class="closing">
        <p>Respectfully yours,</p>

        @if($travelOrder->isPersonal())
        <div class="signature-block">
            <p class="signatory-name">{{ strtoupper($travelOrder->traveler->name) }}</p>
            <p class="signatory-title">{{ $travelOrder->traveler->requested_position ?? 'Faculty' }}</p>
            <p class="signatory-title">{{ $travelOrder->department->name }}</p>
        </div>

        @if($travelOrder->noter)
        <div class="signature-block" style="margin-top: 0.5in;">
            <p style="font-size: 11pt; margin-bottom: 0.4in;"><strong>Noted by:</strong></p>
            <p class="signatory-name">{{ strtoupper($travelOrder->noter->name) }}</p>
            <p class="signatory-title">{{ $travelOrder->noter->requested_position ?? 'Dean' }}</p>
            <p class="signatory-title">{{ $travelOrder->department->name }}</p>
        </div>
        @endif

        @else
        <div class="signature-block">
            <p class="signatory-name">{{ strtoupper($travelOrder->dean->name) }}</p>
            <p class="signatory-title">{{ $travelOrder->dean->requested_position ?? 'Dean' }}</p>
            <p class="signatory-title">{{ $travelOrder->department->name }}</p>
        </div>
        @endif
    </div>

</div>
</body>
</html>
