@extends('layouts.app')

@section('title', 'Endorsement Letter')
@section('eyebrow', 'Endorsement Letter')
@section('page_title', $endorsementLetter->invitation->event_name)

@section('content')
@php
    $isDean = $user->id === $endorsementLetter->dean_id;
    $isReviewer = $user->role === 'approver' && in_array($user->approver_type, ['vp_academic', 'vp_research']);
    $canReviewThis = $isReviewer && (
        ($endorsementLetter->category === 'academic' && $user->approver_type === 'vp_academic') ||
        ($endorsementLetter->category === 'research' && $user->approver_type === 'vp_research')
    );
    $canEdit = $isDean && ($endorsementLetter->isDraft() || $endorsementLetter->isRejected());
@endphp

<div class="max-w-4xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-slate-500">
        @if($isReviewer)
            <a href="{{ route('endorsement-letters.index') }}" class="hover:text-ua-red-600">Endorsements</a>
        @elseif($isDean)
            <a href="{{ route('endorsement-letters.my') }}" class="hover:text-ua-red-600">My Endorsements</a>
        @else
            <span class="text-slate-500">Endorsement</span>
        @endif
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium truncate">{{ $endorsementLetter->invitation->event_name }}</span>
    </div>

    {{-- Status banner --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-lg font-semibold">Endorsement Letter</h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $endorsementLetter->statusBadgeClass() }}">
                        {{ ucfirst($endorsementLetter->status) }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        {{ $endorsementLetter->category === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                        {{ ucfirst($endorsementLetter->category) }}
                    </span>
                </div>
                <p class="text-sm text-slate-600">{{ $endorsementLetter->invitation->event_name }}</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    Dean: <strong>{{ $endorsementLetter->dean->name }}</strong>
                    @if($endorsementLetter->submitted_at)
                        &nbsp;·&nbsp; Submitted {{ $endorsementLetter->submitted_at->format('F j, Y, g:i A') }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('endorsement-letters.letter', $endorsementLetter) }}" target="_blank"
                   class="flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium rounded-xl">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    View Formal Letter
                </a>
                @if($canEdit)
                <a href="{{ route('endorsement-letters.edit', $endorsementLetter) }}"
                   class="flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                    {{ $endorsementLetter->isRejected() ? 'Revise &amp; Resubmit' : 'Edit Draft' }}
                </a>
                @endif
            </div>
        </div>

        @if($endorsementLetter->isApproved())
            @php $sig = $endorsementLetter->reviewSignature(); @endphp
            <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                <div class="flex items-start gap-2 mb-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-700 mt-0.5 shrink-0"></i>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-emerald-800">Approved by {{ $endorsementLetter->reviewer->name ?? $endorsementLetter->reviewerLabel() }}</p>
                        <p class="text-xs text-emerald-700">{{ $endorsementLetter->reviewed_at?->format('F j, Y, g:i A') }}</p>
                        @if($endorsementLetter->review_remarks)
                            <p class="text-xs text-emerald-700 mt-1">Remarks: {{ $endorsementLetter->review_remarks }}</p>
                        @endif
                    </div>
                </div>

                {{-- Digital signature block --}}
                @if($sig)
                <div class="mt-3 pt-3 border-t border-emerald-200 flex items-center gap-4 flex-wrap">
                    <div class="bg-white border border-emerald-200 rounded-lg p-2 shrink-0">
                        <img src="{{ route('signatures.verify.image', $sig->verification_code) }}"
                             alt="Signature of {{ $sig->signer_name_snapshot }}"
                             class="h-16 w-auto max-w-[200px] object-contain">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-emerald-600">Digitally Signed by Authorized Officer</p>
                        <p class="text-sm font-semibold text-slate-800">{{ $sig->signer_name_snapshot }}</p>
                        <p class="text-xs text-slate-500">{{ $sig->signer_position_snapshot ?? '—' }}</p>
                        <p class="text-[11px] text-slate-400 mt-1 font-mono">
                            Verify Code: <a href="{{ route('signatures.verify', $sig->verification_code) }}"
                                       target="_blank"
                                       class="text-ua-red-600 hover:underline">{{ $sig->verification_code }}</a>
                        </p>
                    </div>
                    {{-- QR Code for verification --}}
                    <div class="shrink-0 flex flex-col items-center gap-1">
                        <a href="{{ route('signatures.verify', $sig->verification_code) }}" target="_blank"
                           class="block bg-white border-2 border-emerald-300 rounded-lg p-1.5 hover:border-emerald-500 transition-colors"
                           title="Scan to verify signature">
                            <img src="{{ route('signatures.verify.qr', $sig->verification_code) }}"
                                 alt="QR Code — Scan to verify" class="w-20 h-20">
                        </a>
                        <p class="text-[9px] text-emerald-700 font-semibold uppercase tracking-wider">Scan to Verify</p>
                    </div>
                </div>
                @endif
            </div>
        @elseif($endorsementLetter->isRejected())
            <div class="mt-4 p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-2">
                <i data-lucide="x-circle" class="w-5 h-5 text-rose-700 mt-0.5 shrink-0"></i>
                <div>
                    <p class="text-sm font-semibold text-rose-800">Returned for Revision by {{ $endorsementLetter->reviewer->name ?? $endorsementLetter->reviewerLabel() }}</p>
                    <p class="text-xs text-rose-700">{{ $endorsementLetter->reviewed_at?->format('F j, Y, g:i A') }}</p>
                    @if($endorsementLetter->review_remarks)
                        <p class="text-xs text-rose-700 mt-1">Remarks: {{ $endorsementLetter->review_remarks }}</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Letter content --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 md:col-span-2">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Reason for Endorsing</h3>
            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $endorsementLetter->reason_for_endorsing }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Justification</h3>
            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $endorsementLetter->justification }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Expected Outcomes</h3>
            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $endorsementLetter->expected_outcomes }}</p>
        </div>


        {{-- Endorsed Staff --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 md:col-span-2">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">
                Endorsed Staff ({{ $endorsementLetter->staff->count() }})
            </h3>
            <div class="space-y-2">
                @foreach($endorsementLetter->staff as $member)
                <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <div class="w-9 h-9 rounded-full bg-ua-red-100 flex items-center justify-center shrink-0">
                        <i data-lucide="user" class="w-4 h-4 text-ua-red-700"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800">{{ $member->name }}</p>
                        <p class="text-xs text-slate-400">
                            @if($member->pivot->position){{ $member->pivot->position }}@endif
                            @if($member->pivot->role_in_event) · {{ $member->pivot->role_in_event }}@endif
                            @if(!$member->pivot->position && !$member->pivot->role_in_event){{ $member->email }}@endif
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Invitation reference --}}
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 md:col-span-2">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Source Invitation</h3>
            <a href="{{ route('invitations.show', $endorsementLetter->invitation) }}"
               class="text-sm text-ua-red-700 hover:underline font-medium">
                {{ $endorsementLetter->invitation->event_name }} →
            </a>
            <p class="text-xs text-slate-500 mt-0.5">{{ $endorsementLetter->invitation->formattedDates() }}</p>
        </div>
    </div>

    {{-- Reviewer Action Panel (VPAA / VPREI) --}}
    @if($canReviewThis && $endorsementLetter->isSubmitted())
    <div class="bg-white rounded-2xl border-2 border-ua-red-200 p-6 space-y-4">
        <h3 class="text-base font-semibold flex items-center gap-2">
            <i data-lucide="gavel" class="w-5 h-5 text-ua-red-600"></i>
            Review Decision
        </h3>

        {{-- Signature warning if missing --}}
        @if(!$user->hasSignature())
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 flex items-start gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-700 mt-0.5 shrink-0"></i>
            <div class="flex-1">
                <p class="text-sm font-semibold text-rose-900">Digital Signature Required</p>
                <p class="text-xs text-rose-700 mt-0.5">You must register your signature before approving endorsements. Your signature will be cryptographically attached to this decision.</p>
                <a href="{{ route('profile.show') }}#signature"
                   class="inline-flex items-center gap-1 mt-2 px-3 py-1.5 text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded-lg">
                    <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                    Set Up Signature
                </a>
            </div>
        </div>
        @else
        {{-- Show signer preview --}}
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex items-center gap-3">
            <div class="bg-white border border-slate-200 rounded p-1.5">
                <img src="{{ $user->signatureUrl() }}" alt="Your signature" class="h-10 w-auto max-w-[140px] object-contain">
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500">Will sign as</p>
                <p class="text-sm font-semibold text-slate-800 truncate">{{ $user->name }}</p>
                <p class="text-xs text-slate-500 truncate">{{ $user->requested_position ?? '—' }}</p>
            </div>
            <i data-lucide="shield-check" class="w-5 h-5 text-emerald-600 shrink-0" title="Verified signature on file"></i>
        </div>
        @endif

        @error('signature')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror

        {{-- Security PIN display --}}
        @if($reviewPin)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
            <i data-lucide="shield-check" class="w-5 h-5 text-amber-700 mt-0.5 shrink-0"></i>
            <div class="flex-1">
                <p class="text-xs font-semibold text-amber-900 uppercase tracking-wide mb-1">Security PIN Required</p>
                <p class="font-mono text-3xl font-bold tracking-[0.4em] text-slate-900 mb-1">{{ $reviewPin }}</p>
                <p class="text-xs text-amber-700">Enter this 6-digit PIN below to authorize your decision. This protects against accidental approvals.</p>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('endorsement-letters.review', $endorsementLetter) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Remarks (optional for approval, required for rejection)</label>
                <textarea name="remarks" rows="3" maxlength="1000"
                          placeholder="Notes for the dean..."
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('remarks') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Security PIN <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="review_pin" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                       autocomplete="off"
                       placeholder="••••••"
                       class="w-full md:w-64 border-2 border-slate-200 rounded-xl px-4 py-3 text-center font-mono text-2xl tracking-[0.4em] focus:outline-none focus:ring-2 focus:ring-ua-red-400 focus:border-ua-red-400"
                       value="{{ old('review_pin') }}">
                @error('review_pin')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                <button type="submit" name="decision" value="approved"
                        onclick="return confirm('Approve this endorsement letter with the entered PIN?');"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    Approve
                </button>
                <button type="submit" name="decision" value="rejected"
                        onclick="return confirm('Return this endorsement to the dean for revision with the entered PIN?');"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-xl">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    Return for Revision
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- When approved but no TO yet — show "auto-generation in progress" notice (rare race condition) --}}
    @if($endorsementLetter->isApproved() && !$endorsementLetter->travelOrder)
        <div class="bg-amber-50 border-2 border-amber-200 rounded-2xl p-5">
            <div class="flex items-start gap-3">
                <i data-lucide="clock" class="w-6 h-6 text-amber-700 shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-base font-semibold text-amber-900">Travel Order Generation Pending</p>
                    <p class="text-sm text-amber-800 mt-1">
                        This endorsement is approved but the system has not yet generated the Travel Order.
                        Please refresh the page in a moment, or contact the system administrator if this persists.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- If TO already exists, link to it --}}
    @if($endorsementLetter->travelOrder)
    <div class="bg-sky-50 border border-sky-200 rounded-2xl p-5 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <p class="text-sm font-semibold text-sky-900">Travel Order Created</p>
            <p class="text-xs text-sky-700">
                @if($endorsementLetter->travelOrder->to_number)
                    Official TO Number: <strong>{{ $endorsementLetter->travelOrder->to_number }}</strong>
                @else
                    Status: {{ ucfirst($endorsementLetter->travelOrder->status) }}
                @endif
            </p>
        </div>
        <a href="{{ route('travel-orders.show', $endorsementLetter->travelOrder) }}"
           class="inline-flex items-center gap-2 px-4 py-2 border border-sky-300 text-sky-700 bg-white text-sm font-medium rounded-xl hover:bg-sky-100">
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
            View Travel Order
        </a>
    </div>
    @endif

</div>
@endsection
