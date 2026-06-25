@extends('layouts.app')

@section('title', 'Edit Endorsement Letter')
@section('eyebrow', 'Endorsement')
@section('page_title', $endorsementLetter->isRejected() ? 'Revise Endorsement Letter' : 'Edit Endorsement Draft')

@section('content')
@php
    $existingStaffIds = $endorsementLetter->staff->pluck('id')->toArray();
    $existingStaffData = $endorsementLetter->staff->keyBy('id');
@endphp

<div class="max-w-4xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('endorsement-letters.show', $endorsementLetter) }}" class="hover:text-ua-red-600">Endorsement</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium">{{ $endorsementLetter->isRejected() ? 'Revise' : 'Edit' }}</span>
    </div>

    {{-- Invitation summary --}}
    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Invitation</p>
        <h2 class="text-lg font-semibold text-slate-800">{{ $invitation->event_name }}</h2>
        <p class="text-sm text-slate-600">{{ $invitation->venue ?? $invitation->destination }}</p>
        <p class="text-sm text-slate-400">{{ $invitation->formattedDates() }}</p>
    </div>

    @if($endorsementLetter->isRejected() && $endorsementLetter->review_remarks)
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 flex items-start gap-3">
        <i data-lucide="message-square-warning" class="w-5 h-5 text-rose-700 mt-0.5 shrink-0"></i>
        <div>
            <p class="text-sm font-semibold text-rose-900">Reviewer Remarks ({{ $endorsementLetter->reviewer->name ?? '' }})</p>
            <p class="text-xs text-rose-700 mt-0.5">{{ $endorsementLetter->review_remarks }}</p>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-sm text-rose-700">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('endorsement-letters.update', $endorsementLetter) }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h3 class="text-base font-semibold flex items-center gap-2">
                <i data-lucide="file-text" class="w-5 h-5 text-ua-red-600"></i>
                Letter Content
            </h3>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Reason for Endorsing <span class="text-rose-500">*</span></label>
                <textarea name="reason_for_endorsing" required rows="3" maxlength="1000"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('reason_for_endorsing', $endorsementLetter->reason_for_endorsing) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Justification <span class="text-rose-500">*</span></label>
                <textarea name="justification" required rows="3" maxlength="1000"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('justification', $endorsementLetter->justification) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Expected Outcomes <span class="text-rose-500">*</span></label>
                <textarea name="expected_outcomes" required rows="3" maxlength="1000"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('expected_outcomes', $endorsementLetter->expected_outcomes) }}</textarea>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h3 class="text-base font-semibold flex items-center gap-2">
                <i data-lucide="users" class="w-5 h-5 text-ua-red-600"></i>
                Endorsed Staff
            </h3>

            <div class="space-y-2 max-h-96 overflow-y-auto pr-2">
                @foreach($staff as $member)
                @php $isSelected = in_array($member->id, $existingStaffIds); @endphp
                <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer staff-row">
                    <input type="checkbox" {{ $isSelected ? 'checked' : '' }} class="staff-checkbox mt-1"
                           onchange="toggleStaff(this)">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800">{{ $member->name }}</p>
                        <p class="text-xs text-slate-400">{{ $member->requested_position ?? ucfirst($member->role) }} · {{ $member->email }}</p>
                        <div class="staff-fields {{ $isSelected ? '' : 'hidden' }} mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                            <input type="hidden" name="staff[{{ $member->id }}][user_id]" value="{{ $member->id }}" {{ $isSelected ? '' : 'disabled' }}>
                            <input type="text" name="staff[{{ $member->id }}][position]" {{ $isSelected ? '' : 'disabled' }}
                                   placeholder="Position (optional)"
                                   value="{{ $existingStaffData[$member->id]->pivot->position ?? $member->requested_position }}"
                                   class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs">
                            <input type="text" name="staff[{{ $member->id }}][role_in_event]" {{ $isSelected ? '' : 'disabled' }}
                                   placeholder="Role in event"
                                   value="{{ $existingStaffData[$member->id]->pivot->role_in_event ?? '' }}"
                                   class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs">
                        </div>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-3">
            <button type="submit" name="action" value="draft"
                    class="inline-flex items-center gap-2 px-4 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl">
                <i data-lucide="save" class="w-4 h-4"></i>
                Save as Draft
            </button>
            <button type="submit" name="action" value="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
                <i data-lucide="send" class="w-4 h-4"></i>
                {{ $endorsementLetter->isRejected() ? 'Resubmit for Review' : 'Submit for Review' }}
            </button>
            <a href="{{ route('endorsement-letters.show', $endorsementLetter) }}"
               class="ml-auto text-sm text-slate-500 hover:text-slate-700">Cancel</a>
        </div>
    </form>
</div>

<script>
    function toggleStaff(checkbox) {
        const row = checkbox.closest('.staff-row');
        const fields = row.querySelector('.staff-fields');
        const inputs = fields.querySelectorAll('input');
        if (checkbox.checked) {
            fields.classList.remove('hidden');
            inputs.forEach(input => input.disabled = false);
        } else {
            fields.classList.add('hidden');
            inputs.forEach(input => input.disabled = true);
        }
    }
</script>
@endsection
