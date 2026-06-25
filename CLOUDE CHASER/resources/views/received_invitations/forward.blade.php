@extends('layouts.app')

@section('title', 'Forward Invitation')
@section('eyebrow', "President's Office")
@section('page_title', 'Forward to Dean')

@section('content')
<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
        <a href="{{ route('received-invitations.index') }}" class="hover:text-ua-red-600">Inbox</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <a href="{{ route('received-invitations.show', $receivedInvitation) }}" class="hover:text-ua-red-600 truncate max-w-[200px]">{{ $receivedInvitation->event_name }}</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium">Forward</span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 class="text-base font-semibold mb-1">Forward to Dean(s)</h2>
        <p class="text-sm text-slate-500 mb-6">
            Send this invitation to one or more deans. Event details from the original invitation are locked. Attached files will be copied to each dean's invitation.
        </p>

        {{-- Locked source banner --}}
        <div class="mb-5 flex items-start gap-3 p-4 bg-slate-50 border border-slate-200 rounded-xl">
            <i data-lucide="inbox" class="w-5 h-5 text-slate-500 mt-0.5 shrink-0"></i>
            <div class="flex-1">
                <p class="text-sm font-semibold text-slate-800">From: {{ $receivedInvitation->sender_org }}</p>
                <p class="text-xs text-slate-600 mt-0.5">{{ $receivedInvitation->event_name }} — {{ $receivedInvitation->formattedDates() }}</p>
                @if($receivedInvitation->event_venue || $receivedInvitation->event_destination)
                    <p class="text-xs text-slate-500 mt-0.5">{{ collect([$receivedInvitation->event_venue, $receivedInvitation->event_destination])->filter()->implode(', ') }}</p>
                @endif
                @if($receivedInvitation->attachments->count())
                    <p class="text-xs text-slate-500 mt-1">
                        <i data-lucide="paperclip" class="w-3 h-3 inline"></i> {{ $receivedInvitation->attachments->count() }} attachment(s) will be copied
                    </p>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('received-invitations.forward.store', $receivedInvitation) }}"
              enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Dean selection --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-medium text-slate-700">
                        Assign to Dean(s) <span class="text-rose-500">*</span>
                        <span class="text-xs font-normal text-slate-400 ml-1">select one or more</span>
                    </label>
                    <button type="button" id="selectAllDeans"
                            class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700 transition-colors">
                        Select all
                    </button>
                </div>
                <div class="border border-slate-200 rounded-xl overflow-hidden max-h-56 overflow-y-auto bg-white">
                    @foreach($deans as $dean)
                    <label class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-slate-50 has-[:checked]:bg-ua-red-50 border-b border-slate-100 last:border-b-0 transition-colors">
                        <input type="checkbox" name="assigned_to[]" value="{{ $dean->id }}"
                               class="dean-cb w-4 h-4 rounded border-slate-300 accent-ua-red-600 shrink-0"
                               {{ in_array($dean->id, old('assigned_to', [])) ? 'checked' : '' }}>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 leading-tight">{{ $dean->name }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $dean->requested_position ?? 'Dean' }} &middot; {{ $dean->department?->abbreviation ?? '—' }}
                            </p>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('assigned_to')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Travel category is set by the Records Office when logging — carried over silently --}}
            <input type="hidden" name="type" value="{{ $receivedInvitation->event_type ?? 'academic' }}">

            {{-- Additional notes from President --}}
            <div>
                <label for="additional_details" class="block text-sm font-medium text-slate-700 mb-1.5">Additional Notes <span class="text-slate-400 text-xs font-normal">(optional)</span></label>
                <textarea name="additional_details" id="additional_details" rows="3"
                          placeholder="Any instructions for the dean (priority, preferred traveler, deadlines)"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('additional_details') }}</textarea>
                <p class="text-xs text-slate-400 mt-1">These notes will be appended to the invitation details sent to the dean.</p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="flex items-center gap-2 px-5 py-2.5 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Send to Selected Deans
                </button>
                <a href="{{ route('received-invitations.show', $receivedInvitation) }}" class="text-sm text-slate-500 hover:text-slate-700 ml-auto">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // Select-all toggle
    const btn = document.getElementById('selectAllDeans');
    if (btn) {
        const cbs = () => document.querySelectorAll('.dean-cb');
        btn.addEventListener('click', () => {
            const allChecked = [...cbs()].every(c => c.checked);
            cbs().forEach(c => c.checked = !allChecked);
            btn.textContent = allChecked ? 'Select all' : 'Deselect all';
        });
    }
})();
</script>
@endsection
