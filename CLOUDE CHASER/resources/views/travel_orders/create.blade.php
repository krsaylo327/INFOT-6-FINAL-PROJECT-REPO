@extends('layouts.app')

@section('title', 'New Travel Order')
@section('eyebrow', 'Dean')
@section('page_title', 'Create Travel Order')

@section('content')
<div class="max-w-2xl mx-auto">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
        <a href="{{ route('travel-orders.index') }}" class="hover:text-ua-red-600">Travel Orders</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium">New Travel Order</span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 class="text-base font-semibold mb-1">New Travel Order</h2>
        <p class="text-sm text-slate-500 mb-6">
            @if($invitation)
                Details from the President's Office are locked. Select who will travel and add the purpose.
            @elseif($travelRequest ?? null)
                Pre-filled from approved travel request. Review and complete the form below.
            @else
                Fill in the details below. The system will auto-format the endorsement letter for printing.
            @endif
        </p>

        {{-- Invitation banner --}}
        @if($invitation)
        <div class="mb-5 flex items-start gap-3 p-4 bg-indigo-50 border border-indigo-200 rounded-xl">
            <i data-lucide="mail" class="w-5 h-5 text-indigo-600 mt-0.5 shrink-0"></i>
            <div>
                <p class="text-sm font-semibold text-indigo-800">From: President's Office</p>
                <p class="text-xs text-indigo-700 mt-0.5">{{ $invitation->event_name }} — {{ $invitation->formattedDates() }}</p>
                <p class="text-xs text-indigo-600 mt-1 line-clamp-2">{{ $invitation->details }}</p>
            </div>
        </div>
        @elseif($travelRequest ?? null)
        <div class="mb-5 flex items-start gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
            <i data-lucide="link" class="w-5 h-5 text-emerald-600 mt-0.5 shrink-0"></i>
            <div>
                <p class="text-sm font-semibold text-emerald-800">Linked to Approved Travel Request</p>
                <p class="text-xs text-emerald-700 mt-0.5">{{ $travelRequest->request_no }} — {{ $travelRequest->destination }}</p>
                <p class="text-xs text-emerald-600 mt-1">
                    {{ $travelRequest->date_from->format('M d') }}–{{ $travelRequest->date_to->format('M d, Y') }}
                    · {{ $travelRequest->user->name }}
                </p>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('travel-orders.store') }}" id="toForm" class="space-y-5">
            @csrf
            @if($invitation)
                <input type="hidden" name="invitation_id" value="{{ $invitation->id }}">
            @endif
            @if($travelRequest ?? null)
                <input type="hidden" name="travel_request_id" value="{{ $travelRequest->id }}">
            @endif

            {{-- ── LOCKED SECTION (from invitation) ─────────────────── --}}
            @if($invitation)

            {{-- Locked: Travel Type --}}
            <input type="hidden" name="type" value="{{ $invitation->type }}">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Travel Type</label>
                <div class="flex items-center gap-3 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        {{ $invitation->type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                        {{ ucfirst($invitation->type) }}
                    </span>
                    <span class="flex items-center gap-1 text-xs text-slate-400 ml-auto">
                        <i data-lucide="lock" class="w-3 h-3"></i> Set by President's Office
                    </span>
                </div>
            </div>

            {{-- Locked: Event Name --}}
            <input type="hidden" name="event_name" value="{{ $invitation->event_name }}">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Event / Conference Name</label>
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                    <p class="text-sm text-slate-700">{{ $invitation->event_name }}</p>
                    <span class="flex items-center gap-1 text-xs text-slate-400 shrink-0 ml-3">
                        <i data-lucide="lock" class="w-3 h-3"></i>
                    </span>
                </div>
            </div>

            {{-- Locked: Venue + Destination --}}
            <input type="hidden" name="venue" value="{{ $invitation->venue }}">
            <input type="hidden" name="destination" value="{{ $invitation->destination }}">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Venue</label>
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                        <p class="text-sm text-slate-700">{{ $invitation->venue ?: '—' }}</p>
                        <i data-lucide="lock" class="w-3 h-3 text-slate-400 shrink-0 ml-2"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Destination</label>
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                        <p class="text-sm text-slate-700">{{ $invitation->destination ?: '—' }}</p>
                        <i data-lucide="lock" class="w-3 h-3 text-slate-400 shrink-0 ml-2"></i>
                    </div>
                </div>
            </div>

            {{-- Locked: Dates --}}
            <input type="hidden" name="date_from" value="{{ $invitation->date_from?->format('Y-m-d') }}">
            <input type="hidden" name="date_to" value="{{ $invitation->date_to?->format('Y-m-d') }}">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date From</label>
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                        <p class="text-sm text-slate-700">{{ $invitation->date_from?->format('M j, Y') ?? '—' }}</p>
                        <i data-lucide="lock" class="w-3 h-3 text-slate-400 shrink-0 ml-2"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date To</label>
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                        <p class="text-sm text-slate-700">{{ $invitation->date_to?->format('M j, Y') ?? '—' }}</p>
                        <i data-lucide="lock" class="w-3 h-3 text-slate-400 shrink-0 ml-2"></i>
                    </div>
                </div>
            </div>

            {{-- ── EDITABLE SECTION ──────────────────────────────────── --}}

            {{-- Traveler (locked to the dean — personal attendance) --}}
            <input type="hidden" name="traveler_ids[]" value="{{ $user->id }}">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Traveler</label>
                <div class="flex items-center gap-3 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <div class="w-9 h-9 rounded-full bg-ua-red-100 flex items-center justify-center shrink-0">
                        <i data-lucide="user-check" class="w-4 h-4 text-ua-red-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 leading-tight">{{ $user->name }} <span class="text-xs font-normal text-slate-400">(You)</span></p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $user->requested_position ?? 'Dean' }} &middot; {{ $user->department?->abbreviation ?? '' }}</p>
                    </div>
                    <span class="flex items-center gap-1 text-xs text-slate-400 shrink-0">
                        <i data-lucide="lock" class="w-3 h-3"></i> Attending personally
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">You accepted this invitation to attend personally, so you are the traveler.</p>
            </div>

            {{-- Purpose (editable, pre-filled from the invitation) --}}
            @php
                $defaultPurpose = 'To attend the ' . $invitation->event_name
                    . ($invitation->venue ? ' at ' . $invitation->venue : '')
                    . ($invitation->destination ? ', ' . $invitation->destination : '') . '.';
            @endphp
            <div>
                <label for="purpose" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Purpose of Travel <span class="text-rose-500">*</span>
                </label>
                <textarea name="purpose" id="purpose" rows="3"
                          placeholder="Briefly describe the purpose of this travel (min. 20 characters)"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('purpose', $defaultPurpose) }}</textarea>
                <p class="text-xs text-slate-400 mt-1">Pre-filled from the invitation — edit if you'd like to add more detail.</p>
                @error('purpose')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            @else
            {{-- ── FULLY EDITABLE (no invitation) ───────────────────── --}}

            {{-- Travel Type --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Travel Type <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 border border-slate-200 rounded-xl p-3.5 cursor-pointer hover:bg-slate-50 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50">
                        <input type="radio" name="type" value="academic" class="mt-0.5"
                               {{ old('type', 'academic') === 'academic' ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-slate-800">Academic</p>
                            <p class="text-xs text-slate-500">Conferences, training, seminars (VPAA endorsement)</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 border border-slate-200 rounded-xl p-3.5 cursor-pointer hover:bg-slate-50 has-[:checked]:border-purple-400 has-[:checked]:bg-purple-50">
                        <input type="radio" name="type" value="research" class="mt-0.5"
                               {{ old('type') === 'research' ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-slate-800">Research</p>
                            <p class="text-xs text-slate-500">Research activities, presentations (VP Research endorsement)</p>
                        </div>
                    </label>
                </div>
                @error('type')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Travelers --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-medium text-slate-700">
                        Travelers <span class="text-rose-500">*</span>
                        <span class="text-xs font-normal text-slate-400 ml-1">select one or more</span>
                    </label>
                    <button type="button" id="selectAllTravelers"
                            class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700 transition-colors">
                        Select all
                    </button>
                </div>
                <div class="border border-slate-200 rounded-xl overflow-hidden max-h-56 overflow-y-auto bg-white">
                    @foreach($travelers as $t)
                    <label class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-slate-50 has-[:checked]:bg-ua-red-50 border-b border-slate-100 last:border-b-0 transition-colors">
                        <input type="checkbox" name="traveler_ids[]" value="{{ $t->id }}"
                               class="traveler-cb w-4 h-4 rounded border-slate-300 accent-ua-red-600 shrink-0"
                               {{ in_array($t->id, old('traveler_ids', [])) ? 'checked' : '' }}>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 leading-tight">{{ $t->name }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $t->requested_position ?? '—' }} &middot; {{ $t->department?->abbreviation ?? 'No dept' }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('traveler_ids')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Event Name --}}
            <div>
                <label for="event_name" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Event / Conference Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="event_name" id="event_name"
                       value="{{ old('event_name') }}"
                       placeholder="e.g. National Conference on Information Technology"
                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                @error('event_name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Venue + Destination --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="venue" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Venue <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="venue" id="venue"
                           value="{{ old('venue') }}"
                           placeholder="e.g. Manila Hotel"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('venue')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="destination" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Destination <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="destination" id="destination"
                           value="{{ old('destination') }}"
                           placeholder="e.g. Manila, Philippines"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('destination')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="date_from" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Date From <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="date_from" id="date_from"
                           value="{{ old('date_from') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('date_from')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Date To <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="date_to" id="date_to"
                           value="{{ old('date_to') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('date_to')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Purpose --}}
            <div>
                <label for="purpose" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Purpose of Travel <span class="text-rose-500">*</span>
                </label>
                <textarea name="purpose" id="purpose" rows="3"
                          placeholder="Briefly describe the purpose of this travel (min. 20 characters)"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('purpose') }}</textarea>
                @error('purpose')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            @endif {{-- end invitation/no-invitation split --}}

            {{-- Receipt / TO Timing --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Travel Order Receipt Timing <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 border border-slate-200 rounded-xl p-3.5 cursor-pointer hover:bg-slate-50 has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50">
                        <input type="radio" name="receipt_timing" value="before_travel" class="mt-0.5 accent-emerald-600"
                               {{ old('receipt_timing', 'before_travel') === 'before_travel' ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-slate-800">Before Travel</p>
                            <p class="text-xs text-slate-500">TO must be received prior to departure (standard)</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 border border-slate-200 rounded-xl p-3.5 cursor-pointer hover:bg-slate-50 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                        <input type="radio" name="receipt_timing" value="after_travel" class="mt-0.5 accent-amber-600"
                               {{ old('receipt_timing') === 'after_travel' ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-slate-800">After Travel</p>
                            <p class="text-xs text-slate-500">TO may be received upon return (urgent/unavoidable travel)</p>
                        </div>
                    </label>
                </div>
                @error('receipt_timing')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" name="action" value="draft"
                        class="flex items-center gap-2 px-5 py-2.5 border border-slate-300 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save as Draft
                </button>
                <button type="submit" name="action" value="submit"
                        class="flex items-center gap-2 px-5 py-2.5 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Submit to President
                </button>
                <a href="{{ route('travel-orders.index') }}" class="text-sm text-slate-500 hover:text-slate-700 ml-auto">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const btn = document.getElementById('selectAllTravelers');
    if (!btn) return;
    const cbs = () => document.querySelectorAll('.traveler-cb');
    btn.addEventListener('click', () => {
        const allChecked = [...cbs()].every(c => c.checked);
        cbs().forEach(c => c.checked = !allChecked);
        btn.textContent = allChecked ? 'Select all' : 'Deselect all';
    });
})();
</script>
@endsection
