@extends('layouts.app')

@section('title', 'Travel Order')
@section('eyebrow', 'Travel Order')
@section('page_title', $travelOrder->to_number ?? 'Travel Order Detail')

@section('content')
@php
    $user = auth()->user();
    $badgeClass = match($travelOrder->status) {
        'draft'              => 'bg-slate-100 text-slate-600',
        'submitted'          => 'bg-amber-100 text-amber-700',
        'pending_signature'  => 'bg-orange-100 text-orange-700',
        'pending_release'    => 'bg-indigo-100 text-indigo-700',
        'issued'             => 'bg-emerald-100 text-emerald-700',
        'active'             => 'bg-sky-100 text-sky-700',
        'returned'           => 'bg-blue-100 text-blue-700',
        'completed'          => 'bg-teal-100 text-teal-700',
        default              => 'bg-slate-100 text-slate-600',
    };

    // Timeline steps (TO process flow)
    $steps = [
        ['key' => 'pending_signature',  'label' => 'TO Generated',        'icon' => 'file-plus'],
        ['key' => 'pending_release',    'label' => 'Signed by President', 'icon' => 'pen-tool'],
        ['key' => 'issued',             'label' => 'Released by Records', 'icon' => 'stamp'],
        ['key' => 'returned',           'label' => 'Travel Attested',     'icon' => 'check-circle-2'],
        ['key' => 'completed',          'label' => 'Closed by Records',   'icon' => 'archive'],
    ];
    $statusOrder = [
        'draft'             => 0,
        'submitted'         => 0,
        'pending_signature' => 0,
        'pending_release'   => 1,
        'issued'            => 2,
        'active'            => 2,
        'returned'          => 3,
        'completed'         => 4,
    ];
    $currentStep = $statusOrder[$travelOrder->status] ?? 0;
@endphp

<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb — points each role back to a page they can actually access --}}
    @php
        $isPresident = $user->role === 'dean' && $user->department?->abbreviation === 'PRES';
        $backRoute = match(true) {
            $isPresident                 => route('president.travel-orders.index'),
            $user->role === 'dean'       => route('travel-orders.index'),
            $user->isRecordsOfficer()    => route('records-office.outgoing'),
            $user->role === 'traveler'   => route('travel-orders.my'),
            default                      => route('dashboard'),
        };
    @endphp
    <div class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ $backRoute }}" class="hover:text-ua-red-600">Travel Orders</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium">{{ $travelOrder->to_number ?? 'Draft' }}</span>
    </div>

    {{-- Header Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-lg font-semibold">
                        {{ $travelOrder->to_number ? 'Travel Order ' . $travelOrder->to_number : 'Travel Order (Draft)' }}
                    </h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                        {{ ucfirst($travelOrder->status) }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        {{ $travelOrder->type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                        {{ ucfirst($travelOrder->type) }}
                    </span>
                </div>
                <p class="text-sm text-slate-600 font-medium">{{ $travelOrder->event_name }}</p>
                <p class="text-sm text-slate-400">{{ $travelOrder->venue }}, {{ $travelOrder->destination }}</p>
                <p class="text-sm text-slate-400 mt-0.5">{{ $travelOrder->formattedDates() }}</p>
            </div>

            {{-- Action buttons --}}
            <div class="flex flex-wrap gap-2">
                @if($travelOrder->isDraft() && $travelOrder->dean_id === $user->id)
                    <form method="POST" action="{{ route('travel-orders.submit', $travelOrder) }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-2 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Submit to President
                        </button>
                    </form>
                @endif

                <a href="{{ route('travel-orders.letter', $travelOrder) }}" target="_blank"
                   class="flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    Print Letter
                </a>

                @if($travelOrder->isIssued())
                    <a href="{{ route('travel-orders.print', $travelOrder) }}" target="_blank"
                       class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        Print Travel Order
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Linked Travel Request --}}
    @if($travelOrder->travelOrder ?? false)
    @endif
    @if($travelOrder->relationLoaded('travelRequest') && $travelOrder->travelRequest)
        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 flex items-center gap-3">
            <i data-lucide="link" class="w-5 h-5 text-indigo-600 shrink-0"></i>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wide">Linked Travel Request</p>
                <p class="text-sm font-medium text-indigo-900 truncate">
                    {{ $travelOrder->travelRequest->request_no }} — {{ $travelOrder->travelRequest->destination }}
                </p>
            </div>
            <a href="{{ route('travel-requests.show', $travelOrder->travelRequest) }}"
               class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-white border border-indigo-200 rounded-lg hover:bg-indigo-50">
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                View Request
            </a>
        </div>
    @endif

    {{-- Tracking Timeline --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Document Tracking</h3>
            @php
                $isTravelerOnTO = $travelOrder->traveler_id === $user->id
                    || $travelOrder->travelers->contains('id', $user->id);
                $canSubmitReturn = in_array($travelOrder->status, ['issued', 'active'])
                    && ($isTravelerOnTO || $travelOrder->dean_id === $user->id);
                $canCloseReturn  = $travelOrder->status === 'returned' && $user->isRecordsOfficer();
            @endphp
            @if($canSubmitReturn)
                <button type="button" onclick="document.getElementById('return-modal').classList.remove('hidden')"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition-colors">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                    Submit Return Attestation
                </button>
            @elseif($canCloseReturn)
                <form method="POST" action="{{ route('travel-orders.close', $travelOrder) }}"
                      onsubmit="return confirm('Confirm closing this Travel Order? This will complete the document lifecycle.')">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
                        <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                        Close Travel Order
                    </button>
                </form>
            @endif
        </div>

        <div class="flex items-start gap-0">
            @foreach($steps as $i => $step)
            @php
                $stepIdx  = $statusOrder[$step['key']] ?? $i;
                $done     = $currentStep > $stepIdx;
                $active   = $currentStep === $stepIdx;
                $isLast   = $loop->last;

                // Timestamp label per step
                $ts = match($step['key']) {
                    'pending_signature'  => $travelOrder->created_at,
                    'pending_release'    => $travelOrder->issued_at,           // President's signature timestamp
                    'issued'             => $travelOrder->records_released_at, // Records' release timestamp
                    'returned'           => $travelOrder->returned_at,
                    'completed'          => $travelOrder->status === 'completed' ? $travelOrder->updated_at : null,
                    default              => null,
                };
                // Only show timestamp for done/active steps
                if (!$done && !$active) $ts = null;
            @endphp

            <div class="flex-1 flex flex-col items-center relative {{ !$isLast ? 'pr-1' : '' }}">
                {{-- Connector line --}}
                @if(!$isLast)
                <div class="absolute top-4 left-1/2 w-full h-0.5 {{ $done ? 'bg-teal-400' : 'bg-slate-200' }}" style="left:50%;width:100%;"></div>
                @endif

                {{-- Circle --}}
                <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center shrink-0
                    {{ $done   ? 'bg-teal-500 text-white'
                    : ($active ? 'bg-ua-red-600 text-white ring-4 ring-ua-red-100'
                               : 'bg-slate-100 text-slate-400') }}">
                    <i data-lucide="{{ $done ? 'check' : $step['icon'] }}" class="w-3.5 h-3.5"></i>
                </div>

                {{-- Label --}}
                <p class="mt-2 text-center text-[10px] font-semibold leading-tight
                    {{ $done ? 'text-teal-700' : ($active ? 'text-ua-red-700' : 'text-slate-400') }}">
                    {{ $step['label'] }}
                </p>
                @if($ts)
                    <p class="mt-0.5 text-center text-[9px] text-slate-400 leading-tight">
                        {{ $ts->format('M j, Y') }}
                    </p>
                @endif
            </div>
            @endforeach
        </div>

        @if($travelOrder->isCompleted())
        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs text-teal-700">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-teal-500 shrink-0"></i>
            Travel Order officially closed by the Records Office.
            @if($travelOrder->returned_at)
                Attestation submitted on {{ $travelOrder->returned_at->format('F j, Y \a\t g:i A') }}.
            @endif
        </div>
        @elseif($travelOrder->isReturned())
        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs text-blue-700">
            <i data-lucide="clock" class="w-4 h-4 text-blue-500 shrink-0"></i>
            Travel attestation submitted on {{ $travelOrder->returned_at->format('F j, Y \a\t g:i A') }}. Awaiting Records Office closure.
        </div>
        @elseif($travelOrder->isIssued())
        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs text-slate-500">
            <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
            The Travel Order has been released. After your travel, the traveler can click <strong class="text-teal-700">Submit Return Attestation</strong> to close the document.
        </div>
        @endif
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Traveler Info --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            @php $allTravelers = $travelOrder->travelers->count() ? $travelOrder->travelers : collect([$travelOrder->traveler])->filter(); @endphp
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide">
                    Traveler{{ $allTravelers->count() > 1 ? 's' : '' }}
                </h3>
                @if($allTravelers->count() > 1)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-ua-red-50 text-ua-red-700">
                        {{ $allTravelers->count() }} people
                    </span>
                @endif
            </div>
            <div class="space-y-3">
                @foreach($allTravelers as $t)
                <div class="{{ !$loop->first ? 'pt-3 border-t border-slate-100' : '' }}">
                    <p class="text-sm font-medium text-slate-800">{{ $t->name }}</p>
                    <p class="text-xs text-slate-400">{{ $t->requested_position ?? '—' }}</p>
                </div>
                @endforeach
                <div class="pt-2 border-t border-slate-100">
                    <p class="text-xs text-slate-400">Department</p>
                    <p class="text-sm text-slate-700">{{ $travelOrder->department->name ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Travel Details --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Travel Details</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-slate-400">Event / Conference</p>
                    <p class="text-sm font-medium text-slate-800">{{ $travelOrder->event_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">Venue</p>
                    <p class="text-sm text-slate-700">{{ $travelOrder->venue }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">Destination</p>
                    <p class="text-sm text-slate-700">{{ $travelOrder->destination }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">Dates</p>
                    <p class="text-sm text-slate-700">{{ $travelOrder->formattedDates() }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">Endorsement Route</p>
                    <p class="text-sm text-slate-700">{{ $travelOrder->vpLabel() }}</p>
                </div>
                @if($travelOrder->receipt_timing)
                <div>
                    <p class="text-xs text-slate-400">TO Receipt Timing</p>
                    @if($travelOrder->receipt_timing === 'after_travel')
                        <span class="inline-flex items-center gap-1 text-sm font-medium text-amber-700">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            After Travel
                        </span>
                        <p class="text-xs text-slate-400 mt-0.5">TO may be received upon return</p>
                    @else
                        <span class="inline-flex items-center gap-1 text-sm font-medium text-emerald-700">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            Before Travel
                        </span>
                        <p class="text-xs text-slate-400 mt-0.5">TO received prior to departure</p>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Purpose --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 md:col-span-2">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Purpose</h3>
            <p class="text-sm text-slate-700 leading-relaxed">{{ $travelOrder->purpose }}</p>
        </div>

        {{-- Created By / Noted By --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            @if($travelOrder->isPersonal())
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Noted By (Dean)</h3>
                <div class="space-y-2">
                    @if($travelOrder->noter)
                        <p class="text-sm font-medium text-slate-800">{{ $travelOrder->noter->name }}</p>
                        <p class="text-xs text-slate-400">{{ $travelOrder->noter->requested_position ?? 'Dean' }}</p>
                    @else
                        <p class="text-sm text-slate-400 italic">No dean noted</p>
                    @endif
                    <p class="text-xs text-slate-400">{{ $travelOrder->created_at->format('F j, Y, g:i A') }}</p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">Personal Request</span>
                </div>
            @else
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Created By (Dean)</h3>
                <div class="space-y-2">
                    <p class="text-sm font-medium text-slate-800">{{ $travelOrder->dean->name }}</p>
                    <p class="text-xs text-slate-400">{{ $travelOrder->dean->department?->name ?? '—' }}</p>
                    <p class="text-xs text-slate-400">{{ $travelOrder->created_at->format('F j, Y, g:i A') }}</p>
                </div>
            @endif
        </div>

        @if($travelOrder->isIssued() || in_array($travelOrder->status, ['active', 'completed']))
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Approved By (President)</h3>
                <p class="text-sm font-medium text-slate-800">{{ $travelOrder->issuer?->name ?? '—' }}</p>
                <p class="text-xs text-slate-400">{{ $travelOrder->issued_at?->format('F j, Y, g:i A') ?? '—' }}</p>
            </div>
            <div class="border-t border-slate-100 pt-3">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Released By (Records Office)</h3>
                <p class="text-sm font-semibold text-emerald-700">{{ $travelOrder->to_number }}</p>
                <p class="text-sm font-medium text-slate-800">{{ $travelOrder->recordsOfficer?->name ?? '—' }}</p>
                <p class="text-xs text-slate-400">{{ $travelOrder->records_released_at?->format('F j, Y, g:i A') ?? '—' }}</p>
            </div>
        </div>
        @elseif($travelOrder->isPendingRelease())
        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-5 space-y-3">
            <div class="flex items-start gap-3">
                <i data-lucide="file-output" class="w-5 h-5 text-indigo-600 mt-0.5 shrink-0"></i>
                <div>
                    <p class="text-sm font-semibold text-indigo-800">Forwarded to Records Office</p>
                    <p class="text-xs text-indigo-700 mt-1">Signed by {{ $travelOrder->issuer?->name ?? 'the President' }} on {{ $travelOrder->issued_at?->format('F j, Y') ?? '—' }}. The Records Office will release the document to the traveler.</p>
                </div>
            </div>
        </div>
        @elseif($travelOrder->isPendingSignature())
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 flex items-start gap-3">
            <i data-lucide="clock" class="w-5 h-5 text-amber-600 mt-0.5 shrink-0"></i>
            <div>
                <p class="text-sm font-semibold text-amber-800">Awaiting President's Signature</p>
                <p class="text-xs text-amber-700 mt-1">This Travel Order has been auto-generated and is awaiting the President's digital signature.</p>
            </div>
        </div>
        @endif

    </div>

    {{-- Funding Source --}}
    @if($travelOrder->budget_code || $travelOrder->grant_account)
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-2 mb-4">
            <i data-lucide="banknote" class="w-4 h-4 text-emerald-600"></i>
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Funding Source</h3>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            @if($travelOrder->budget_code)
            <div>
                <p class="text-xs text-slate-400">Budget Code</p>
                <p class="text-sm font-mono font-semibold text-slate-800 tracking-wide">{{ $travelOrder->budget_code }}</p>
            </div>
            @endif
            @if($travelOrder->grant_account)
            <div>
                <p class="text-xs text-slate-400">Grant Account</p>
                <p class="text-sm font-mono font-semibold text-slate-800 tracking-wide">{{ $travelOrder->grant_account }}</p>
                @if($travelOrder->grant_title)
                    <p class="text-xs text-slate-500 mt-0.5">{{ $travelOrder->grant_title }}</p>
                @endif
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Expense Report Section --}}
    @php
        $report = $travelOrder->expenseReport;
        $canSubmitExpense = in_array($user->id, [$travelOrder->traveler_id, $travelOrder->dean_id]) || $user->role === 'admin';
    @endphp
    @if($travelOrder->isCompleted() || $report)
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-2 mb-4">
            <i data-lucide="receipt" class="w-4 h-4 text-emerald-600"></i>
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Expense Reconciliation</h3>
            @if($report)
                <span class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $report->statusBadgeClass() }}">
                    {{ ucfirst($report->status) }}
                </span>
            @endif
        </div>

        @if($report)
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <p class="text-xs text-slate-400">Total Submitted</p>
                    <p class="text-lg font-semibold text-slate-800">₱{{ number_format($report->total_amount, 2) }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $report->items->count() }} item(s)</p>
                </div>
                <a href="{{ route('expense-reports.show', $report) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-emerald-700 border border-emerald-200 rounded-xl hover:bg-emerald-50">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    View Expense Report
                </a>
            </div>
        @elseif($canSubmitExpense)
            <p class="text-sm text-slate-500 mb-3">This Travel Order is complete. Submit an expense report with itemized receipts.</p>
            <a href="{{ route('expense-reports.create', $travelOrder) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Submit Expense Report
            </a>
        @else
            <p class="text-sm text-slate-400 py-2">No expense report has been submitted yet.</p>
        @endif
    </div>
    @endif

</div>

{{-- Return Attestation Modal --}}
@if(isset($canSubmitReturn) && $canSubmitReturn)
<div id="return-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl">
        <form method="POST" action="{{ route('travel-orders.return', $travelOrder) }}">
            @csrf
            <div class="px-6 py-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-teal-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900">Submit Return Attestation</h3>
                        <p class="text-xs text-slate-500">Confirm you have returned from this travel.</p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-5 space-y-4">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800 leading-relaxed">
                    <p class="font-semibold mb-1">📌 Attestation Notice</p>
                    <p>By submitting, you certify that you have completed the official travel as authorized by this Travel Order. The Records Office will verify your attestation and officially close this document.</p>
                </div>

                <div>
                    <label for="return_report" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Brief Travel Report / Attestation <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="return_report" id="return_report" rows="5" required minlength="20" maxlength="2000"
                              placeholder="Briefly describe the activities attended, key takeaways, and confirm your return from travel. (Minimum 20 characters)"
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"></textarea>
                    @error('return_report')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2 rounded-b-2xl">
                <button type="button" onclick="document.getElementById('return-modal').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold bg-teal-600 hover:bg-teal-700 text-white rounded-lg">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    Submit Attestation
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
