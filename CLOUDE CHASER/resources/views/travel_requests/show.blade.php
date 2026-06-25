@extends('layouts.app')

@section('title', 'Request ' . $travelRequest->request_no)
@section('eyebrow', 'Travel Request')
@section('page_title', $travelRequest->request_no)

@section('content')
    @php
        $currentUser = auth()->user();
        $isAssignment = $travelRequest->type === 'assigned';
        $needsAck = $isAssignment
            && $travelRequest->status === 'assigned'
            && is_null($travelRequest->acknowledged_at)
            && $currentUser?->id === $travelRequest->user_id;
    @endphp

    <div class="max-w-5xl">
        <div class="flex items-center justify-between mb-6">
            <a href="{{ url()->previous() }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
            <div class="flex items-center gap-2">
                @if($isAssignment)
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-[11px] font-medium bg-indigo-50 text-indigo-700">
                        <i data-lucide="user-plus" class="w-3 h-3"></i>
                        Assigned
                    </span>
                @endif
                @include('partials.status-pill', ['status' => $travelRequest->status])
            </div>
        </div>

        {{-- Assignment acknowledgement banner --}}
        @if($needsAck)
            <div class="mb-6 bg-indigo-50 border border-indigo-200 rounded-2xl p-5 sm:p-6">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shrink-0">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-indigo-900">This trip was assigned to you</h3>
                        <p class="text-sm text-indigo-800 mt-0.5">
                            <span class="font-semibold">{{ $travelRequest->assigner->name ?? 'An approver' }}</span>
                            assigned this travel to you
                            {{ $travelRequest->created_at->diffForHumans() }}.
                            Please acknowledge to start the approval chain, or decline if you cannot accept.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('assignments.acknowledge', $travelRequest) }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            Acknowledge & Submit for Approval
                        </button>
                    </form>
                    <form method="POST" action="{{ route('assignments.decline', $travelRequest) }}" class="inline"
                          onsubmit="return confirm('Decline this travel assignment? The assigner will be notified.');">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-rose-700 bg-white border border-rose-200 hover:bg-rose-50 rounded-xl">
                            <i data-lucide="x" class="w-4 h-4"></i>
                            Decline
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Assigned-by info banner (for non-traveler viewers or already-acknowledged assignments) --}}
        @if($isAssignment && !$needsAck)
            <div class="mb-6 bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center gap-3 text-sm">
                <i data-lucide="user-plus" class="w-4 h-4 text-indigo-600 shrink-0"></i>
                <div class="flex-1">
                    <span class="text-slate-700">
                        Assigned by <span class="font-semibold">{{ $travelRequest->assigner->name ?? 'Unknown' }}</span>
                    </span>
                    @if($travelRequest->acknowledged_at)
                        <span class="text-slate-400 mx-1">•</span>
                        <span class="text-emerald-700">
                            Acknowledged {{ $travelRequest->acknowledged_at->diffForHumans() }}
                        </span>
                    @elseif($travelRequest->status === 'declined')
                        <span class="text-slate-400 mx-1">•</span>
                        <span class="text-rose-700 font-medium">Declined by traveler</span>
                    @endif
                </div>
            </div>
        @endif

        {{-- Header card --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
            <div class="p-5 sm:p-6 bg-gradient-to-br from-ua-red-50 to-white border-b border-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-mono text-slate-500">{{ $travelRequest->request_no }}</p>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1 flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-5 h-5 text-ua-red-600"></i>
                            {{ $travelRequest->destination }}
                        </h2>
                        <p class="text-sm text-slate-600 mt-1">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 inline mr-1"></i>
                            {{ $travelRequest->date_from->format('M d, Y') }} – {{ $travelRequest->date_to->format('M d, Y') }}
                            <span class="ml-2 text-slate-400">({{ $travelRequest->date_from->diffInDays($travelRequest->date_to) + 1 }} days)</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                <div class="p-5 sm:p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Traveler</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-ua-red-500 to-ua-red-700 flex items-center justify-center text-white font-semibold">
                            {{ strtoupper(substr($travelRequest->user->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">{{ $travelRequest->user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $travelRequest->department->name }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Estimated Cost</p>
                    <p class="text-2xl font-bold text-slate-900">
                        ₱{{ number_format($travelRequest->estimated_cost, 2) }}
                    </p>
                </div>
            </div>

            <div class="p-5 sm:p-6 border-t border-slate-100">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Purpose</p>
                <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $travelRequest->purpose }}</p>
            </div>
        </div>

        {{-- Approval timeline --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="git-branch" class="w-5 h-5 text-slate-600"></i>
                <h3 class="font-semibold">Approval Timeline</h3>
            </div>

            <div class="p-5 sm:p-6">
                @if($travelRequest->approvals->isEmpty())
                    <div class="text-center py-8">
                        <i data-lucide="hourglass" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                        <p class="text-sm text-slate-500">No approval records yet.</p>
                    </div>
                @else
                    <ol class="relative border-l-2 border-slate-100 ml-3 space-y-6">
                        @foreach($travelRequest->approvals->sortBy('level') as $approval)
                            @php
                                $isApproved = $approval->action === 'approved';
                                $isRejected = $approval->action === 'rejected';
                                $isPending  = $approval->action === 'pending';
                            @endphp
                            <li class="ml-6 relative">
                                <span class="absolute -left-[31px] top-0 w-8 h-8 rounded-full flex items-center justify-center ring-4 ring-white
                                    {{ $isApproved ? 'bg-emerald-100 text-emerald-700' : '' }}
                                    {{ $isRejected ? 'bg-rose-100 text-rose-700' : '' }}
                                    {{ $isPending  ? 'bg-amber-100 text-amber-700' : '' }}">
                                    <i data-lucide="{{ $isApproved ? 'check' : ($isRejected ? 'x' : 'clock') }}" class="w-4 h-4"></i>
                                </span>

                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900">
                                            Level {{ $approval->level }} —
                                            {{ $approval->approver->name ?? 'Unknown' }}
                                        </p>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ $approval->acted_at ? $approval->acted_at->format('M d, Y h:i A') : 'Pending' }}
                                        </p>
                                        @if($approval->remarks)
                                            <p class="mt-2 text-sm text-slate-700 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                                “{{ $approval->remarks }}”
                                            </p>
                                        @endif
                                    </div>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold uppercase tracking-wider
                                        {{ $isApproved ? 'bg-emerald-50 text-emerald-700' : '' }}
                                        {{ $isRejected ? 'bg-rose-50 text-rose-700' : '' }}
                                        {{ $isPending  ? 'bg-amber-50 text-amber-700' : '' }}">
                                        {{ $approval->action }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>

        {{-- Attachments --}}
        <div class="mt-6 bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <i data-lucide="paperclip" class="w-5 h-5 text-slate-600"></i>
                    <h3 class="font-semibold">Supporting Documents</h3>
                    @if($travelRequest->attachments->isNotEmpty())
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium">
                            {{ $travelRequest->attachments->count() }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="p-5 sm:p-6 space-y-4">
                @if($travelRequest->attachments->isEmpty())
                    <p class="text-sm text-slate-400">No documents attached yet.</p>
                @else
                    <ul class="space-y-2">
                        @foreach($travelRequest->attachments as $att)
                            <li class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <i data-lucide="file-text" class="w-5 h-5 text-slate-400 shrink-0"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ $att->original_name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $att->humanSize() }} · uploaded {{ $att->created_at->diffForHumans() }}</p>
                                </div>
                                <a href="{{ $att->downloadUrl() }}"
                                   class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    Download
                                </a>
                                @if($currentUser->id === $att->uploaded_by || $currentUser->role === 'admin')
                                    <form method="POST" action="{{ route('attachments.destroy', $att) }}"
                                          onsubmit="return confirm('Remove this attachment?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="shrink-0 p-1.5 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if(in_array($travelRequest->status, ['pending', 'assigned', 'approved']) ||
                    $currentUser->id === $travelRequest->user_id || $currentUser->role === 'admin')
                    <form method="POST" action="{{ route('attachments.store', $travelRequest) }}"
                          enctype="multipart/form-data" class="pt-2 border-t border-slate-100">
                        @csrf
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Upload more documents</label>
                        <div class="flex items-center gap-3">
                            <input type="file" name="attachments[]" multiple
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                   class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                            <button type="submit"
                                    class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-ua-red-600 hover:bg-ua-red-700 rounded-lg">
                                <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                Upload
                            </button>
                        </div>
                        @error('attachments.*')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </form>
                @endif
            </div>
        </div>

        {{-- Travel Order linkage --}}
        @if($travelRequest->status === 'approved' && in_array($currentUser->role, ['dean', 'admin']))
            <div class="mt-6 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center gap-2">
                    <i data-lucide="file-badge" class="w-5 h-5 text-indigo-600"></i>
                    <h3 class="font-semibold">Travel Order</h3>
                </div>
                <div class="p-5 sm:p-6">
                    @if($travelRequest->travelOrder)
                        <div class="flex items-center gap-3 p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-emerald-900">Travel Order created</p>
                                <p class="text-xs text-emerald-700">
                                    {{ $travelRequest->travelOrder->to_number ?? 'Draft / Pending issuance' }}
                                    · Status: {{ ucfirst($travelRequest->travelOrder->status) }}
                                </p>
                            </div>
                            <a href="{{ route('travel-orders.show', $travelRequest->travelOrder) }}"
                               class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-white border border-emerald-200 rounded-lg hover:bg-emerald-50">
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                View TO
                            </a>
                        </div>
                    @else
                        <p class="text-sm text-slate-600 mb-4">This travel request is approved. You can now generate a formal Travel Order document from it.</p>
                        <a href="{{ route('travel-orders.create', ['travel_request' => $travelRequest->id]) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Create Travel Order
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- QR + Print card --}}
        <div class="mt-6 bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="qr-code" class="w-5 h-5 text-slate-600"></i>
                <h3 class="font-semibold">Verification &amp; Print</h3>
            </div>
            <div class="p-5 sm:p-6 grid sm:grid-cols-[auto_1fr] gap-5 sm:gap-6 items-center">
                <div class="flex justify-center sm:justify-start">
                    <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-sm">
                        <img src="{{ route('travel-requests.qr', $travelRequest) }}"
                             alt="QR Code for {{ $travelRequest->request_no }}"
                             class="w-[180px] h-[180px] block">
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Scan to verify</p>
                        <p class="text-sm text-slate-700 mt-1 leading-relaxed">
                            This QR encodes a signed link to the public trace page. Security personnel
                            can scan it to confirm this travel order's current status in real time —
                            no login required.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <a href="{{ $traceUrl }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl">
                            <i data-lucide="external-link" class="w-4 h-4"></i>
                            Open Public Trace
                        </a>
                        <a href="{{ route('travel-requests.print', $travelRequest) }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold text-white bg-ua-red-600 hover:bg-ua-red-700 rounded-xl shadow-sm">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                            Print Travel Order
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Audit log timeline --}}
        <div class="mt-6">
            @include('partials.audit-log', ['logs' => $logs ?? collect()])
        </div>
    </div>
@endsection
