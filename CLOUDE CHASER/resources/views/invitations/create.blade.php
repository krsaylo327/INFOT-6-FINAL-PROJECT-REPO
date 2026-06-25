@extends('layouts.app')

@section('title', 'Forward Invitation')
@section('eyebrow', "President's Office")
@section('page_title', 'Forward Invitation to Dean')

@section('content')
<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
        <a href="{{ route('invitations.index') }}" class="hover:text-ua-red-600">Invitations</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium">Forward New Invitation</span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 class="text-base font-semibold mb-1">Forward External Invitation</h2>
        <p class="text-sm text-slate-500 mb-6">
            The invitation details will be sent to the assigned dean, who will then create the Travel Order and pick the traveler.
        </p>

        <form method="POST" action="{{ route('invitations.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Assign to Dean(s) --}}
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

            {{-- Travel Type --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Travel Type <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 border border-slate-200 rounded-xl p-3.5 cursor-pointer hover:bg-slate-50 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50">
                        <input type="radio" name="type" value="academic" class="mt-0.5" {{ old('type', 'academic') === 'academic' ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-slate-800">Academic</p>
                            <p class="text-xs text-slate-500">Conference, training, seminar</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 border border-slate-200 rounded-xl p-3.5 cursor-pointer hover:bg-slate-50 has-[:checked]:border-purple-400 has-[:checked]:bg-purple-50">
                        <input type="radio" name="type" value="research" class="mt-0.5" {{ old('type') === 'research' ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-slate-800">Research</p>
                            <p class="text-xs text-slate-500">Research presentation, study</p>
                        </div>
                    </label>
                </div>
                @error('type')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
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
                    <label for="venue" class="block text-sm font-medium text-slate-700 mb-1.5">Venue</label>
                    <input type="text" name="venue" id="venue"
                           value="{{ old('venue') }}"
                           placeholder="e.g. SMX Convention Center"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('venue')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="destination" class="block text-sm font-medium text-slate-700 mb-1.5">Destination</label>
                    <input type="text" name="destination" id="destination"
                           value="{{ old('destination') }}"
                           placeholder="e.g. Pasay, Metro Manila"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('destination')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Dates (optional) --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="date_from" class="block text-sm font-medium text-slate-700 mb-1.5">Date From <span class="text-slate-400 text-xs">(optional)</span></label>
                    <input type="date" name="date_from" id="date_from"
                           value="{{ old('date_from') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('date_from')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-slate-700 mb-1.5">Date To <span class="text-slate-400 text-xs">(optional)</span></label>
                    <input type="date" name="date_to" id="date_to"
                           value="{{ old('date_to') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('date_to')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Details --}}
            <div>
                <label for="details" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Invitation Details <span class="text-rose-500">*</span>
                </label>
                <textarea name="details" id="details" rows="4"
                          placeholder="Paste or summarize the invitation details for the dean..."
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('details') }}</textarea>
                @error('details')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Attachments --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Attach Invitation Files
                    <span class="text-xs font-normal text-slate-400 ml-1">optional · up to 5 files · 10 MB each</span>
                </label>
                <label for="attachments"
                       class="flex flex-col items-center justify-center gap-2 w-full border-2 border-dashed border-slate-200 rounded-xl py-6 px-4 cursor-pointer hover:border-ua-red-300 hover:bg-ua-red-50 transition-colors group">
                    <i data-lucide="upload-cloud" class="w-7 h-7 text-slate-300 group-hover:text-ua-red-400 transition-colors"></i>
                    <p class="text-sm text-slate-500 group-hover:text-ua-red-600">Click to upload or drag &amp; drop</p>
                    <p class="text-xs text-slate-400">PDF, Word (.doc/.docx), JPG, PNG</p>
                    <input type="file" id="attachments" name="attachments[]" multiple
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="hidden" onchange="updateFileList(this)">
                </label>
                <ul id="file-list" class="mt-2 space-y-1"></ul>
                @error('attachments')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="flex items-center gap-2 px-5 py-2.5 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Forward to Dean
                </button>
                <a href="{{ route('invitations.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    const btn = document.getElementById('selectAllDeans');
    const cbs = () => document.querySelectorAll('.dean-cb');
    btn.addEventListener('click', () => {
        const allChecked = [...cbs()].every(c => c.checked);
        cbs().forEach(c => c.checked = !allChecked);
        btn.textContent = allChecked ? 'Select all' : 'Deselect all';
    });
})();

function updateFileList(input) {
    const list = document.getElementById('file-list');
    list.innerHTML = '';
    const icons = { 'application/pdf': 'file-text', 'image/jpeg': 'image', 'image/png': 'image', 'image/jpg': 'image' };
    [...input.files].forEach(f => {
        const icon = icons[f.type] || 'file';
        const size = f.size >= 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.round(f.size / 1024) + ' KB';
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2 text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2';
        li.innerHTML = `<i data-lucide="${icon}" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i><span class="flex-1 truncate">${f.name}</span><span class="text-slate-400 shrink-0">${size}</span>`;
        list.appendChild(li);
    });
    if (window.lucide) lucide.createIcons();
}
</script>
@endsection
