@extends('layouts.app')

@section('title', 'Create Endorsement Letter')
@section('eyebrow', 'Endorsement')
@section('page_title', 'Endorse Staff for: ' . $invitation->event_name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('invitations.show', $invitation) }}" class="hover:text-ua-red-600">Invitation</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium">Endorse Staff</span>
    </div>

    {{-- Invitation summary (locked) --}}
    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Invitation</p>
                <h2 class="text-lg font-semibold text-slate-800">{{ $invitation->event_name }}</h2>
                <p class="text-sm text-slate-600">{{ $invitation->venue ?? $invitation->destination }}</p>
                <p class="text-sm text-slate-400">{{ $invitation->formattedDates() }}</p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                {{ $invitation->type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                {{ ucfirst($invitation->type) }}
            </span>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-sm text-rose-700">
        <p class="font-semibold mb-1">Please fix the following errors:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('endorsement-letters.store', $invitation) }}" class="space-y-6">
        @csrf

        {{-- Letter Content --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h3 class="text-base font-semibold flex items-center gap-2">
                <i data-lucide="file-text" class="w-5 h-5 text-ua-red-600"></i>
                Letter Content
            </h3>

            <p class="text-xs text-slate-500 -mt-1">
                <i data-lucide="lightbulb" class="w-3.5 h-3.5 inline text-amber-500"></i>
                Click a common answer to fill it in, then edit the wording if you like — or just type your own.
            </p>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Reason for Endorsing <span class="text-rose-500">*</span>
                </label>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    @foreach([
                        'I am unable to attend personally due to a prior commitment, so I am endorsing qualified staff to represent the college.',
                        'Because of a scheduling conflict with my administrative duties, I am endorsing staff to attend in my place.',
                        'To give faculty professional growth opportunities, I am endorsing staff to attend on behalf of the college.',
                    ] as $opt)
                    <button type="button" data-fill="reason_for_endorsing" data-text="{{ $opt }}"
                            class="answer-chip text-xs px-2.5 py-1 rounded-full bg-slate-100 hover:bg-ua-red-50 hover:text-ua-red-700 text-slate-600 border border-slate-200 transition-colors text-left">
                        {{ \Illuminate\Support\Str::limit($opt, 48) }}
                    </button>
                    @endforeach
                </div>
                <textarea name="reason_for_endorsing" id="reason_for_endorsing" required rows="3" maxlength="1000"
                          placeholder="Why are you unable to attend personally? (e.g. scheduling conflict, prior commitment...)"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('reason_for_endorsing') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Justification <span class="text-rose-500">*</span>
                </label>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    @foreach([
                        'The endorsed staff are directly involved in the relevant programs and will benefit most from this activity.',
                        'These staff have the technical background and responsibilities most aligned with the event objectives.',
                        'The endorsed staff are the subject-matter experts best positioned to apply the learnings to college operations.',
                    ] as $opt)
                    <button type="button" data-fill="justification" data-text="{{ $opt }}"
                            class="answer-chip text-xs px-2.5 py-1 rounded-full bg-slate-100 hover:bg-ua-red-50 hover:text-ua-red-700 text-slate-600 border border-slate-200 transition-colors text-left">
                        {{ \Illuminate\Support\Str::limit($opt, 48) }}
                    </button>
                    @endforeach
                </div>
                <textarea name="justification" id="justification" required rows="3" maxlength="1000"
                          placeholder="Why are these staff the right people to attend?"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('justification') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Expected Outcomes <span class="text-rose-500">*</span>
                </label>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    @foreach([
                        'The staff will gain updated knowledge and skills that will be shared with the college through a re-echo session.',
                        'Participation will strengthen the college capabilities and improve delivery of related programs and services.',
                        'The university will benefit from new competencies, networks, and best practices brought back by the attendees.',
                    ] as $opt)
                    <button type="button" data-fill="expected_outcomes" data-text="{{ $opt }}"
                            class="answer-chip text-xs px-2.5 py-1 rounded-full bg-slate-100 hover:bg-ua-red-50 hover:text-ua-red-700 text-slate-600 border border-slate-200 transition-colors text-left">
                        {{ \Illuminate\Support\Str::limit($opt, 48) }}
                    </button>
                    @endforeach
                </div>
                <textarea name="expected_outcomes" id="expected_outcomes" required rows="3" maxlength="1000"
                          placeholder="What will the university gain from their attendance?"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('expected_outcomes') }}</textarea>
            </div>
        </div>

        {{-- Endorsed Staff --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h3 class="text-base font-semibold flex items-center gap-2">
                <i data-lucide="users" class="w-5 h-5 text-ua-red-600"></i>
                Endorsed Staff
            </h3>
            <p class="text-xs text-slate-500">Select at least one staff member to endorse for this event.</p>

            @if($staff->isEmpty())
                <div class="p-4 bg-slate-50 border border-dashed border-slate-300 rounded-xl text-sm text-slate-500 text-center">
                    No eligible staff found in your department.
                </div>
            @else
                <div class="space-y-2 max-h-96 overflow-y-auto pr-2">
                    @foreach($staff as $member)
                    <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer staff-row">
                        <input type="checkbox" class="staff-checkbox mt-1" data-user-id="{{ $member->id }}"
                               onchange="toggleStaff(this, {{ $member->id }})">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800">{{ $member->name }}</p>
                            <p class="text-xs text-slate-400">{{ $member->requested_position ?? ucfirst($member->role) }} · {{ $member->email }}</p>
                            <div class="staff-fields hidden mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                                <input type="hidden" name="staff[{{ $member->id }}][user_id]" value="{{ $member->id }}" disabled>
                                <input type="text" name="staff[{{ $member->id }}][position]" disabled
                                       placeholder="Position (optional)"
                                       value="{{ $member->requested_position }}"
                                       class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                                <input type="text" name="staff[{{ $member->id }}][role_in_event]" disabled
                                       placeholder="Role in event (e.g. Speaker)"
                                       class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Submit buttons --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-3">
            <button type="submit" name="action" value="draft"
                    class="inline-flex items-center gap-2 px-4 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl">
                <i data-lucide="save" class="w-4 h-4"></i>
                Save as Draft
            </button>
            <button type="submit" name="action" value="submit"
                    onclick="return confirm('Submit endorsement to {{ $invitation->type === 'research' ? 'VPREI' : 'VPAA' }} for review?');"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
                <i data-lucide="send" class="w-4 h-4"></i>
                Submit for Review
            </button>
            <a href="{{ route('invitations.show', $invitation) }}"
               class="ml-auto text-sm text-slate-500 hover:text-slate-700">Cancel</a>
        </div>
    </form>
</div>

<script>
    function toggleStaff(checkbox, userId) {
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

    // Common-answer chips — fill the target textarea, then let the dean edit freely
    document.querySelectorAll('.answer-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            const target = document.getElementById(chip.dataset.fill);
            if (!target) return;
            target.value = chip.dataset.text;
            target.focus();
            target.dispatchEvent(new Event('input'));
        });
    });
</script>
@endsection
