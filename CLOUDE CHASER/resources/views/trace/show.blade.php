<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Travel Trace — {{ $data['request_no'] }}</title>
    @vite(['resources/css/app.css'])
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <style>
        html, body { background: #f8fafc; }
        .brand-band { background: linear-gradient(90deg, #c40000 0%, #8b0000 100%); }
        .timeline-line { position: absolute; left: 19px; top: 40px; bottom: 0; width: 2px; background: #e2e8f0; }
        .timeline-item:last-child .timeline-line { display: none; }
    </style>
</head>
<body class="min-h-screen antialiased text-slate-800">

    <div class="brand-band h-2 w-full"></div>

    <div class="max-w-xl mx-auto px-4 py-6 sm:py-10">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-6">
            <img src="{{ asset('images/ua-logo.png') }}" alt="UA" class="w-10 h-10 rounded-full bg-white ring-1 ring-slate-200" onerror="this.style.display='none'">
            <div>
                <p class="text-[11px] font-semibold tracking-wider uppercase text-slate-500">University of Antique</p>
                <h1 class="text-base font-bold text-slate-900">UA-TRaMP Travel Trace</h1>
            </div>
            <span class="ml-auto font-mono text-xs text-slate-400">{{ $data['request_no'] }}</span>
        </div>

        {{-- Status hero --}}
        @php
            $statusColor = match($data['status']) {
                'approved'  => ['bg-emerald-50','text-emerald-700','border-emerald-200','check-circle-2'],
                'rejected'  => ['bg-rose-50','text-rose-700','border-rose-200','x-circle'],
                'declined'  => ['bg-rose-50','text-rose-700','border-rose-200','x-circle'],
                'pending'   => ['bg-amber-50','text-amber-700','border-amber-200','clock'],
                'assigned'  => ['bg-indigo-50','text-indigo-700','border-indigo-200','user-plus'],
                default     => ['bg-slate-50','text-slate-700','border-slate-200','circle'],
            };
        @endphp

        <div class="{{ $statusColor[0] }} border {{ $statusColor[2] }} rounded-2xl p-4 flex items-center gap-3 mb-6 shadow-sm">
            <i data-lucide="{{ $statusColor[3] }}" class="w-7 h-7 {{ $statusColor[1] }} shrink-0"></i>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider {{ $statusColor[1] }}">Overall Status</p>
                <p class="text-lg font-bold {{ $statusColor[1] }} capitalize">{{ $data['status'] }}</p>
            </div>
        </div>

        {{-- Trip summary --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6 shadow-sm">
            <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-3">Trip Summary</p>
            <div class="flex items-start gap-3 mb-3">
                <i data-lucide="map-pin" class="w-4 h-4 text-ua-red-600 mt-0.5 shrink-0"></i>
                <p class="font-semibold text-slate-900">{{ $data['destination'] }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-0.5">Travel Dates</p>
                    <p class="font-medium">{{ $data['date_from'] }} — {{ $data['date_to'] }}</p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-0.5">Traveler</p>
                    <p class="font-medium flex items-center gap-1.5">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-200 text-slate-700 text-[9px] font-bold">{{ $data['traveler_initials'] }}</span>
                        Verified staff
                    </p>
                </div>
            </div>
        </div>

        {{-- Full lifecycle timeline --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6 shadow-sm">
            <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-5">Workflow Lifecycle</p>

            <div class="space-y-0">

                {{-- Stage 1: Request Submitted --}}
                @include('trace._stage', [
                    'icon'    => 'file-text',
                    'label'   => 'Request Submitted',
                    'status'  => 'done',
                    'detail'  => 'Request ' . $data['request_no'] . ' created',
                    'last'    => false,
                ])

                {{-- Stage 2: Acknowledgment (assigned trips only) --}}
                @if($data['type'] === 'assigned')
                    @if($data['acknowledged_at'])
                        @include('trace._stage', [
                            'icon'   => 'user-check',
                            'label'  => 'Acknowledged by Traveler',
                            'status' => 'done',
                            'detail' => $data['acknowledged_at'],
                            'last'   => false,
                        ])
                    @else
                        @include('trace._stage', [
                            'icon'   => 'user-plus',
                            'label'  => 'Awaiting Acknowledgment',
                            'status' => 'waiting',
                            'detail' => 'Traveler has not yet acknowledged this assignment',
                            'last'   => false,
                        ])
                    @endif
                @endif

                {{-- Stage 3: Approval chain --}}
                @forelse($data['approvals'] as $i => $ap)
                    @php
                        $apStatus = match($ap['action']) {
                            'approved' => 'done',
                            'rejected' => 'rejected',
                            default    => 'waiting',
                        };
                        $apDetail = $ap['action'] === 'approved'
                            ? 'Approved on ' . $ap['acted']
                            : ($ap['action'] === 'rejected' ? 'Rejected on ' . $ap['acted'] : 'Awaiting decision');
                    @endphp
                    @include('trace._stage', [
                        'icon'   => $ap['action'] === 'approved' ? 'check-circle-2' : ($ap['action'] === 'rejected' ? 'x-circle' : 'clock'),
                        'label'  => 'Level ' . $ap['level'] . ' Approval',
                        'status' => $apStatus,
                        'detail' => $apDetail,
                        'last'   => false,
                    ])
                @empty
                    @include('trace._stage', [
                        'icon'   => 'clock',
                        'label'  => 'Approval Chain',
                        'status' => 'waiting',
                        'detail' => 'No approval chain initiated yet',
                        'last'   => false,
                    ])
                @endforelse

                {{-- Stage 4: Itinerary --}}
                @if($data['itinerary'])
                    @php
                        $itinStatus = match($data['itinerary']['status']) {
                            'confirmed', 'completed' => 'done',
                            default => 'waiting',
                        };
                        $itinDetail = $data['itinerary']['departure_place'] && $data['itinerary']['arrival_place']
                            ? $data['itinerary']['departure_place'] . ' → ' . $data['itinerary']['arrival_place']
                            : ('Status: ' . ucfirst($data['itinerary']['status']));
                        if ($data['itinerary']['transport_mode']) $itinDetail .= ' via ' . $data['itinerary']['transport_mode'];
                    @endphp
                    @include('trace._stage', [
                        'icon'   => 'map',
                        'label'  => 'Itinerary',
                        'status' => $itinStatus,
                        'detail' => $itinDetail,
                        'last'   => false,
                    ])
                @else
                    @include('trace._stage', [
                        'icon'   => 'map',
                        'label'  => 'Itinerary',
                        'status' => $data['is_fully_approved'] ? 'waiting' : 'pending',
                        'detail' => 'Not yet filed',
                        'last'   => false,
                    ])
                @endif

                {{-- Stage 5: Liquidation --}}
                @if($data['liquidation'])
                    @php
                        $liqStatus = match($data['liquidation']['status']) {
                            'approved', 'settled' => 'done',
                            'submitted'           => 'waiting',
                            default               => 'pending',
                        };
                        $liqDetail = 'Claimed: ₱' . number_format($data['liquidation']['total_claimed'] ?? 0, 2);
                        if ($data['liquidation']['total_approved']) {
                            $liqDetail .= ' · Approved: ₱' . number_format($data['liquidation']['total_approved'], 2);
                        }
                    @endphp
                    @include('trace._stage', [
                        'icon'   => 'receipt',
                        'label'  => 'Liquidation',
                        'status' => $liqStatus,
                        'detail' => $liqDetail,
                        'last'   => true,
                    ])
                @else
                    @include('trace._stage', [
                        'icon'   => 'receipt',
                        'label'  => 'Liquidation',
                        'status' => 'pending',
                        'detail' => 'Not yet submitted',
                        'last'   => true,
                    ])
                @endif

            </div>
        </div>

        {{-- Authorization notice --}}
        @if($data['is_fully_approved'])
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 flex items-start gap-3 mb-6">
                <i data-lucide="shield-check" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"></i>
                <div>
                    <p class="text-sm font-semibold text-emerald-800">Authorized Travel</p>
                    <p class="text-xs text-emerald-700 mt-0.5">This trip has completed the full approval chain and is authorized for the dates shown.</p>
                </div>
            </div>
        @elseif($data['is_terminated'])
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 flex items-start gap-3 mb-6">
                <i data-lucide="shield-off" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5"></i>
                <div>
                    <p class="text-sm font-semibold text-rose-800">Not Authorized</p>
                    <p class="text-xs text-rose-700 mt-0.5">This request was not approved. Do not clear the traveler based on this order.</p>
                </div>
            </div>
        @else
            <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 flex items-start gap-3 mb-6">
                <i data-lucide="hourglass" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
                <div>
                    <p class="text-sm font-semibold text-amber-800">Approval In Progress</p>
                    <p class="text-xs text-amber-700 mt-0.5">This travel order has not yet completed its full approval chain.</p>
                </div>
            </div>
        @endif

        <div class="text-center text-[11px] text-slate-400">
            <p>Scanned {{ $data['scanned_at'] }}</p>
            <p class="mt-1">UA-TRaMP • Travel Management &amp; Itinerary Platform</p>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
