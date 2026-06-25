@extends('layouts.app')

@section('title', 'Received Invitation')
@section('eyebrow', "President's Inbox")
@section('page_title', $receivedInvitation->event_name)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('received-invitations.index') }}" class="hover:text-ua-red-600">Inbox</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium truncate">{{ $receivedInvitation->event_name }}</span>
    </div>

    {{-- Header --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-lg font-semibold">{{ $receivedInvitation->event_name }}</h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $receivedInvitation->statusBadgeClass() }}">
                        {{ ucfirst($receivedInvitation->status) }}
                    </span>
                    @if($receivedInvitation->event_type)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                            {{ $receivedInvitation->event_type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                            {{ ucfirst($receivedInvitation->event_type) }}
                        </span>
                    @endif
                </div>
                @if($receivedInvitation->event_venue || $receivedInvitation->event_destination)
                    <p class="text-sm text-slate-600">
                        {{ collect([$receivedInvitation->event_venue, $receivedInvitation->event_destination])->filter()->implode(', ') }}
                    </p>
                @endif
                <p class="text-sm text-slate-400 mt-0.5">{{ $receivedInvitation->formattedDates() }}</p>
                <p class="text-xs text-slate-400 mt-2">
                    <i data-lucide="inbox" class="w-3 h-3 inline"></i>
                    Received {{ $receivedInvitation->received_at->format('F j, Y') }}
                </p>
            </div>

            @if($receivedInvitation->isNew())
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('received-invitations.forward', $receivedInvitation) }}"
                       class="flex items-center gap-2 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Forward to Dean
                    </a>
                    <button type="button" onclick="document.getElementById('declineModal').classList.remove('hidden')"
                            class="flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium rounded-xl">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        Decline
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Detail Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Sender</h3>
            <p class="text-sm font-medium text-slate-800">{{ $receivedInvitation->sender_org }}</p>
            @if($receivedInvitation->sender_name)
                <p class="text-xs text-slate-600 mt-1">{{ $receivedInvitation->sender_name }}</p>
            @endif
            @if($receivedInvitation->sender_email)
                <p class="text-xs text-slate-400 mt-1">
                    <i data-lucide="mail" class="w-3 h-3 inline"></i> {{ $receivedInvitation->sender_email }}
                </p>
            @endif
            @if($receivedInvitation->sender_phone)
                <p class="text-xs text-slate-400 mt-0.5">
                    <i data-lucide="phone" class="w-3 h-3 inline"></i> {{ $receivedInvitation->sender_phone }}
                </p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Status</h3>
            @if($receivedInvitation->isNew())
                <p class="text-sm font-medium text-amber-700">Awaiting Action</p>
                <p class="text-xs text-slate-400 mt-1">Not yet forwarded to a dean</p>
            @elseif($receivedInvitation->isForwarded())
                <p class="text-sm font-medium text-emerald-700">Forwarded</p>
                <p class="text-xs text-slate-500 mt-1">Sent to {{ $receivedInvitation->forwardedInvitations->count() }} dean(s)</p>
                <div class="mt-2 space-y-1">
                    @foreach($receivedInvitation->forwardedInvitations as $inv)
                        <a href="{{ route('invitations.show', $inv) }}" class="block text-xs text-ua-red-600 hover:text-ua-red-700">
                            → {{ $inv->assignedDean->name }} ({{ $inv->assignedDean->department?->abbreviation ?? '—' }})
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-sm font-medium text-slate-600">Declined</p>
                @if($receivedInvitation->declined_reason)
                    <p class="text-xs text-slate-500 mt-1 italic">{{ $receivedInvitation->declined_reason }}</p>
                @endif
            @endif
        </div>

        @if($receivedInvitation->description)
        <div class="bg-white rounded-2xl border border-slate-200 p-5 md:col-span-2">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Description / Notes</h3>
            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $receivedInvitation->description }}</p>
        </div>
        @endif

        {{-- Attachments with inline viewer --}}
        @if($receivedInvitation->attachments->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-5 md:col-span-2">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">
                Attached Documents ({{ $receivedInvitation->attachments->count() }})
            </h3>
            <div class="space-y-3">
                @foreach($receivedInvitation->attachments as $att)
                <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    @if($att->isImage())
                        <a href="{{ route('received-invitations.attachments.view', [$receivedInvitation, $att]) }}" target="_blank"
                           class="w-12 h-12 rounded-lg overflow-hidden bg-white border border-slate-200 shrink-0">
                            <img src="{{ route('received-invitations.attachments.view', [$receivedInvitation, $att]) }}"
                                 class="w-full h-full object-cover" alt="{{ $att->original_name }}">
                        </a>
                    @else
                        <div class="w-12 h-12 rounded-lg bg-ua-red-50 flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $att->isPdf() ? 'file-text' : 'file' }}" class="w-5 h-5 text-ua-red-600"></i>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $att->original_name }}</p>
                        <p class="text-xs text-slate-400">{{ $att->formattedSize() }} · {{ $att->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($att->isImage() || $att->isPdf())
                            <a href="{{ route('received-invitations.attachments.view', [$receivedInvitation, $att]) }}" target="_blank"
                               class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                View
                            </a>
                        @endif
                        <a href="{{ route('received-invitations.attachments.download', [$receivedInvitation, $att]) }}"
                           class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-white hover:border-ua-red-200 hover:text-ua-red-600 transition-colors">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            Download
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Decline Modal --}}
    @if($receivedInvitation->isNew())
    <div id="declineModal" class="hidden fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6">
            <h3 class="text-base font-semibold mb-2">Decline this invitation?</h3>
            <p class="text-sm text-slate-500 mb-4">Provide a brief reason. The invitation will be archived in the inbox as declined.</p>

            <form method="POST" action="{{ route('received-invitations.decline', $receivedInvitation) }}">
                @csrf
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Reason <span class="text-rose-500">*</span></label>
                <textarea name="declined_reason" rows="3" required
                          placeholder="e.g. Schedule conflicts with mid-term exams; no available representative"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none"></textarea>

                <div class="flex items-center gap-3 mt-4">
                    <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-xl">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        Confirm Decline
                    </button>
                    <button type="button" onclick="document.getElementById('declineModal').classList.add('hidden')"
                            class="text-sm text-slate-500 hover:text-slate-700 ml-auto">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
