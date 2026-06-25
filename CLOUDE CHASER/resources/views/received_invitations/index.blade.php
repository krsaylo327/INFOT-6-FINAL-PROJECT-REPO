@extends('layouts.app')

@section('title', 'Inbox')
@section('eyebrow', "President's Office")
@section('page_title', 'Invitation Inbox')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold">Inbox</h2>
            <p class="text-sm text-slate-500">External invitations received by the President's Office</p>
        </div>
    </div>


    @if($received->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <i data-lucide="inbox" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
            <p class="text-sm font-medium text-slate-500">Inbox is empty</p>
            <p class="text-xs text-slate-400 mt-1">Invitations logged by the Records Office will appear here</p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left">
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Sender</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Event</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Received</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Event Dates</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($received as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800 truncate max-w-[200px]">{{ $r->sender_org }}</p>
                            <p class="text-xs text-slate-400">{{ $r->sender_name ?? 'No contact name' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-slate-700 truncate max-w-[260px]">{{ $r->event_name }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                @if($r->event_type)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold
                                        {{ $r->event_type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                                        {{ ucfirst($r->event_type) }}
                                    </span>
                                @endif
                                @if($r->attachments->count())
                                    <span class="inline-flex items-center gap-1 text-[10px] text-slate-500">
                                        <i data-lucide="paperclip" class="w-3 h-3"></i> {{ $r->attachments->count() }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $r->received_at->format('M j, Y') }}
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $r->formattedDates() }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $r->statusBadgeClass() }}">
                                {{ ucfirst($r->status) }}
                            </span>
                            @if($r->isForwarded() && $r->forwardedInvitations->count() > 1)
                                <p class="text-[10px] text-slate-400 mt-0.5">to {{ $r->forwardedInvitations->count() }} deans</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('received-invitations.show', $r) }}"
                               class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">View →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
