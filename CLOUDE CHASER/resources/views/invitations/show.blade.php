@extends('layouts.app')

@section('title', 'Invitation Detail')
@section('eyebrow', 'Invitation')
@section('page_title', $invitation->event_name)

@section('content')
@php $user = auth()->user(); @endphp
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-slate-500">
        @if($user->department?->abbreviation === 'PRES')
            <a href="{{ route('invitations.index') }}" class="hover:text-ua-red-600">Invitations</a>
        @else
            <a href="{{ route('invitations.inbox') }}" class="hover:text-ua-red-600">Invitations</a>
        @endif
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium truncate">{{ $invitation->event_name }}</span>
    </div>

    {{-- Read-only indicator for dean recipient --}}
    @if($invitation->assigned_to === $user->id)
    <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl">
        <i data-lucide="lock" class="w-4 h-4 text-slate-500 shrink-0"></i>
        <p class="text-xs text-slate-600">
            These details were entered by the <strong>President's Office</strong> and are <strong>read-only</strong>.
            Choose to <strong>Accept &amp; Attend personally</strong>, <strong>Endorse Staff</strong>, or <strong>Decline</strong>.
        </p>
    </div>
    @endif

    {{-- Response choice panel: Accept vs Endorse vs Decline --}}
    @if($invitation->assigned_to === $user->id && $invitation->canRespond() && !$invitation->travelOrder)
    <div class="bg-white rounded-2xl border-2 border-ua-red-200 p-6 space-y-4">
        <div>
            <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                <i data-lucide="git-branch" class="w-5 h-5 text-ua-red-600"></i>
                How will you respond to this invitation?
            </h3>
            <p class="text-xs text-slate-500 mt-1">Choose one option below.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            {{-- Option A: Accept & Attend Personally --}}
            <form method="POST" action="{{ route('invitations.accept', $invitation) }}"
                  onsubmit="return confirm('Confirm you will attend this event personally? You will then create your Travel Order.');">
                @csrf
                <button type="submit"
                        class="w-full h-full text-left p-4 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition-colors group">
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="user-check" class="w-5 h-5 text-emerald-700"></i>
                        <span class="text-sm font-semibold text-emerald-800">Accept &amp; Attend</span>
                    </div>
                    <p class="text-xs text-emerald-700 leading-relaxed">
                        I will attend personally. Create a Travel Order directly (no further approval needed).
                    </p>
                </button>
            </form>

            {{-- Option B: Endorse Staff --}}
            <form method="POST" action="{{ route('invitations.endorse', $invitation) }}">
                @csrf
                <button type="submit"
                        class="w-full h-full text-left p-4 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-xl transition-colors group">
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="users" class="w-5 h-5 text-indigo-700"></i>
                        <span class="text-sm font-semibold text-indigo-800">Endorse Staff</span>
                    </div>
                    <p class="text-xs text-indigo-700 leading-relaxed">
                        I cannot attend. Endorse staff to attend instead — requires {{ $invitation->type === 'research' ? 'VPREI' : 'VPAA' }} approval.
                    </p>
                </button>
            </form>

            {{-- Option C: Decline --}}
            <button type="button" onclick="document.getElementById('decline-modal').classList.remove('hidden')"
                    class="w-full h-full text-left p-4 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition-colors">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="x-circle" class="w-5 h-5 text-rose-700"></i>
                    <span class="text-sm font-semibold text-rose-800">Decline</span>
                </div>
                <p class="text-xs text-rose-700 leading-relaxed">
                    Decline the invitation. The President's Office will be notified.
                </p>
            </button>
        </div>
    </div>

    {{-- Decline modal --}}
    <div id="decline-modal" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6">
            <h3 class="text-base font-semibold mb-2">Decline Invitation</h3>
            <p class="text-xs text-slate-500 mb-4">Please provide a reason for declining this invitation.</p>
            <form method="POST" action="{{ route('invitations.reject', $invitation) }}">
                @csrf
                <textarea name="reject_reason" required rows="3" minlength="5" maxlength="500"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-400 resize-none"
                          placeholder="e.g. Schedule conflict with the accreditation visit..."></textarea>
                <div class="flex items-center gap-2 mt-4">
                    <button type="button" onclick="document.getElementById('decline-modal').classList.add('hidden')"
                            class="px-4 py-2 text-sm font-medium text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-xl">
                        Confirm Decline
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Status: Endorsed (endorsement letter exists) --}}
    @if($invitation->isEndorsed() && $invitation->endorsementLetter)
    <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-5 flex items-start gap-3">
        <i data-lucide="users" class="w-5 h-5 text-indigo-700 mt-0.5 shrink-0"></i>
        <div class="flex-1">
            <p class="text-sm font-semibold text-indigo-900">Endorsement Letter Submitted</p>
            <p class="text-xs text-indigo-700 mt-0.5">
                Status: <strong>{{ ucfirst($invitation->endorsementLetter->status) }}</strong>
                @if($invitation->endorsementLetter->reviewer)
                    &nbsp;·&nbsp; Reviewed by {{ $invitation->endorsementLetter->reviewer->name }}
                @endif
            </p>
            <a href="{{ route('endorsement-letters.show', $invitation->endorsementLetter) }}"
               class="text-xs text-indigo-700 underline hover:text-indigo-800 mt-1 inline-block">
                View endorsement letter →
            </a>
        </div>
    </div>
    @endif

    {{-- Status: Declined --}}
    @if($invitation->isRejected())
    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-5 flex items-start gap-3">
        <i data-lucide="x-circle" class="w-5 h-5 text-rose-700 mt-0.5 shrink-0"></i>
        <div>
            <p class="text-sm font-semibold text-rose-900">Invitation Declined</p>
            @if($invitation->reject_reason)
                <p class="text-xs text-rose-700 mt-0.5">{{ $invitation->reject_reason }}</p>
            @endif
        </div>
    </div>
    @endif

    {{-- Header --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-lg font-semibold">{{ $invitation->event_name }}</h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        {{ $invitation->type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                        {{ ucfirst($invitation->type) }}
                    </span>
                </div>
                @if($invitation->venue || $invitation->destination)
                    <p class="text-sm text-slate-600">
                        {{ collect([$invitation->venue, $invitation->destination])->filter()->implode(', ') }}
                    </p>
                @endif
                <p class="text-sm text-slate-400 mt-0.5">{{ $invitation->formattedDates() }}</p>
            </div>

            @if(!$invitation->travelOrder && $invitation->assigned_to === $user->id)
                <a href="{{ route('travel-orders.create', ['invitation' => $invitation->id]) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl shrink-0">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Create Travel Order
                </a>
            @elseif($invitation->travelOrder)
                <a href="{{ route('travel-orders.show', $invitation->travelOrder) }}"
                   class="flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50 shrink-0">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    View Travel Order
                </a>
            @endif
        </div>
    </div>

    {{-- Detail Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Forwarded By</h3>
            <p class="text-sm font-medium text-slate-800">{{ $invitation->issuer->name }}</p>
            <p class="text-xs text-slate-400">{{ $invitation->issuer->requested_position ?? "President's Office" }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ $invitation->created_at->format('F j, Y, g:i A') }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Assigned To</h3>
            <p class="text-sm font-medium text-slate-800">{{ $invitation->assignedDean->name }}</p>
            <p class="text-xs text-slate-400">{{ $invitation->assignedDean->department?->name ?? '—' }}</p>
            <div class="mt-2">
                @if($invitation->travelOrder)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700">
                        <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                        Travel Order Created
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700">
                        Awaiting Travel Order
                    </span>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 md:col-span-2">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Invitation Details</h3>
            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $invitation->details }}</p>
        </div>

        {{-- Attachments with inline viewer (forwarded from the Records Office logging) --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 md:col-span-2">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="paperclip" class="w-4 h-4 text-slate-400"></i>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide">
                    Invitation Documents ({{ $invitation->attachments->count() }})
                </h3>
                <span class="text-[11px] text-slate-400">— attached by the Records Office</span>
            </div>

            @if($invitation->attachments->isEmpty())
                <div class="flex items-center gap-2 px-3 py-4 bg-slate-50 border border-dashed border-slate-200 rounded-xl text-sm text-slate-400">
                    <i data-lucide="file-x" class="w-4 h-4 shrink-0"></i>
                    No documents were attached to this invitation.
                </div>
            @else
            <div class="space-y-3">
                @foreach($invitation->attachments as $att)
                <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    @if($att->isImage())
                        <a href="{{ route('invitations.attachments.view', [$invitation, $att]) }}" target="_blank"
                           class="w-12 h-12 rounded-lg overflow-hidden bg-white border border-slate-200 shrink-0">
                            <img src="{{ route('invitations.attachments.view', [$invitation, $att]) }}"
                                 class="w-full h-full object-cover" alt="{{ $att->original_name }}">
                        </a>
                    @else
                        <div class="w-12 h-12 rounded-lg bg-ua-red-50 flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $att->isPdf() ? 'file-text' : 'file' }}" class="w-5 h-5 text-ua-red-600"></i>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $att->original_name }}</p>
                        <p class="text-xs text-slate-400">{{ $att->formattedSize() }} &nbsp;·&nbsp; Uploaded {{ $att->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($att->isImage() || $att->isPdf())
                            <a href="{{ route('invitations.attachments.view', [$invitation, $att]) }}" target="_blank"
                               class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                View
                            </a>
                        @endif
                        <a href="{{ route('invitations.attachments.download', [$invitation, $att]) }}"
                           class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-white hover:border-ua-red-200 hover:text-ua-red-600 transition-colors">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            Download
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        @if($invitation->travelOrder)
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 md:col-span-2 flex items-start gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 mt-0.5 shrink-0"></i>
            <div>
                <p class="text-sm font-semibold text-emerald-800">Travel Order Created</p>
                <p class="text-xs text-emerald-700 mt-0.5">
                    Traveler: {{ $invitation->travelOrder->traveler->name }} &nbsp;·&nbsp;
                    Status: {{ ucfirst($invitation->travelOrder->status) }}
                    @if($invitation->travelOrder->to_number)
                        &nbsp;·&nbsp; {{ $invitation->travelOrder->to_number }}
                    @endif
                </p>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
