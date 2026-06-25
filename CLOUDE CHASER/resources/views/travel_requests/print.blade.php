<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Travel Order — {{ $travelRequest->request_no }}</title>
    <style>
        @page { size: A4 portrait; margin: 20mm 18mm; }
        * { box-sizing: border-box; }
        body {
            font-family: "Times New Roman", Georgia, serif;
            color: #111;
            background: #fff;
            font-size: 12pt;
            line-height: 1.45;
            margin: 0; padding: 24px;
        }
        .sheet {
            max-width: 780px;
            margin: 0 auto;
            padding: 32px;
            background: #fff;
        }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; border-bottom: 2px solid #c40000; padding-bottom: 14px; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { width: 56px; height: 56px; object-fit: contain; }
        .brand h1 { margin: 0; font-size: 16pt; letter-spacing: 0.6px; }
        .brand p  { margin: 0; font-size: 9pt; letter-spacing: 2px; text-transform: uppercase; color: #555; }
        .qr-box { text-align: center; }
        .qr-box img { width: 110px; height: 110px; display: block; }
        .qr-box p  { margin-top: 4px; font-size: 8pt; color: #666; font-family: monospace; }
        h2.title { text-align: center; margin: 24px 0 6px; font-size: 14pt; letter-spacing: 4px; text-transform: uppercase; }
        .reqno { text-align: center; font-family: monospace; font-size: 10pt; color: #666; margin-bottom: 20px; }
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; margin-bottom: 18px; }
        .meta-row label { display: block; font-size: 8pt; letter-spacing: 1px; text-transform: uppercase; color: #888; margin-bottom: 2px; }
        .meta-row .val { font-size: 11.5pt; font-weight: 600; border-bottom: 1px dotted #999; padding-bottom: 2px; }
        .section-title { font-size: 9pt; letter-spacing: 2px; text-transform: uppercase; color: #c40000; margin: 20px 0 6px; font-weight: bold; border-bottom: 1px solid #c40000; padding-bottom: 3px; }
        .purpose-box { border: 1px solid #ddd; padding: 10px 12px; min-height: 72px; font-size: 11pt; background: #fafafa; white-space: pre-line; }
        .approval-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .approval-table th, .approval-table td { border: 1px solid #ccc; padding: 8px 10px; font-size: 10pt; text-align: left; }
        .approval-table th { background: #f0f0f0; text-transform: uppercase; font-size: 9pt; letter-spacing: 1px; }
        .badge { display: inline-block; font-size: 8.5pt; padding: 2px 8px; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 700; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-pending  { background: #fef3c7; color: #92400e; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .footer-note { margin-top: 28px; padding-top: 14px; border-top: 1px dashed #aaa; font-size: 9.5pt; color: #555; display: flex; justify-content: space-between; gap: 12px; }
        .footer-note small { font-size: 8pt; }
        .print-bar {
            position: sticky; top: 0; background: #1f2937; color: #fff;
            padding: 10px 16px; display: flex; justify-content: space-between; align-items: center;
            font-family: system-ui, sans-serif; font-size: 13px;
        }
        .print-bar button, .print-bar a {
            background: #c40000; color: #fff; border: none; padding: 6px 14px;
            border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; font-size: 13px;
        }
        .print-bar a.sec { background: #374151; margin-right: 8px; }
        @media print {
            .print-bar { display: none; }
            body { padding: 0; }
            .sheet { padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>

    <div class="print-bar">
        <span>📄 Travel Order — {{ $travelRequest->request_no }}</span>
        <div>
            <a class="sec" href="{{ route('travel-requests.show', $travelRequest) }}">← Back</a>
            <button onclick="window.print()">🖨 Print</button>
        </div>
    </div>

    <div class="sheet">

        <div class="header">
            <div class="brand">
                <img src="{{ asset('images/ua-logo.png') }}" alt="UA" onerror="this.style.display='none'">
                <div>
                    <p>University of Antique</p>
                    <h1>UA-TRaMP</h1>
                    <small style="font-size: 8pt; color:#777;">Travel Management &amp; Itinerary Platform</small>
                </div>
            </div>
            <div class="qr-box">
                <img src="{{ route('travel-requests.qr', $travelRequest) }}" alt="QR Code">
                <p>{{ $travelRequest->request_no }}</p>
            </div>
        </div>

        <h2 class="title">Official Travel Order</h2>
        <div class="reqno">{{ $travelRequest->request_no }} &nbsp;·&nbsp; Issued {{ $travelRequest->submitted_at?->format('F d, Y') ?? $travelRequest->created_at->format('F d, Y') }}</div>

        <div class="meta-grid">
            <div class="meta-row">
                <label>Traveler</label>
                <div class="val">{{ $travelRequest->user->name }}</div>
            </div>
            <div class="meta-row">
                <label>Department</label>
                <div class="val">{{ $travelRequest->department->name }}</div>
            </div>
            <div class="meta-row">
                <label>Destination</label>
                <div class="val">{{ $travelRequest->destination }}</div>
            </div>
            <div class="meta-row">
                <label>Inclusive Dates</label>
                <div class="val">
                    {{ $travelRequest->date_from->format('M d, Y') }} – {{ $travelRequest->date_to->format('M d, Y') }}
                    ({{ $travelRequest->date_from->diffInDays($travelRequest->date_to) + 1 }} days)
                </div>
            </div>
            <div class="meta-row">
                <label>Estimated Cost</label>
                <div class="val">₱{{ number_format($travelRequest->estimated_cost, 2) }}</div>
            </div>
            <div class="meta-row">
                <label>Request Type</label>
                <div class="val">
                    {{ $travelRequest->isAssigned() ? 'Assigned' : 'Self-Request' }}
                    @if($travelRequest->isAssigned() && $travelRequest->assigner)
                        <small style="display:block; font-weight: normal; color:#666; font-size:9pt;">by {{ $travelRequest->assigner->name }}</small>
                    @endif
                </div>
            </div>
        </div>

        <div class="section-title">Purpose of Travel</div>
        <div class="purpose-box">{{ $travelRequest->purpose }}</div>

        <div class="section-title">Approval Chain</div>
        <table class="approval-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Level</th>
                    <th>Approver</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 140px;">Date Acted</th>
                </tr>
            </thead>
            <tbody>
                @forelse($travelRequest->approvals->sortBy('level') as $approval)
                    <tr>
                        <td style="text-align: center; font-weight: bold;">L{{ $approval->level }}</td>
                        <td>{{ $approval->approver->name ?? '—' }}</td>
                        <td>
                            @php
                                $cls = match($approval->action) {
                                    'approved' => 'badge-approved',
                                    'rejected' => 'badge-rejected',
                                    default    => 'badge-pending',
                                };
                            @endphp
                            <span class="badge {{ $cls }}">{{ $approval->action }}</span>
                        </td>
                        <td>{{ $approval->acted_at?->format('M d, Y h:i A') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center; color:#888;">No approval chain recorded.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer-note">
            <div>
                <strong>Verification:</strong> Scan the QR code above or visit the trace link to confirm the current status of this travel order in real time.
                <br><small>Generated {{ now()->format('M d, Y h:i A') }}</small>
            </div>
            <div style="text-align: right; font-size: 9pt;">
                <div style="margin-top: 24px; border-top: 1px solid #333; width: 200px; padding-top: 2px; text-align: center;">
                    Signature over printed name
                </div>
            </div>
        </div>
    </div>

</body>
</html>
