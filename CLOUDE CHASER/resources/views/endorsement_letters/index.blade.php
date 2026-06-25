@extends('layouts.app')

@section('title', 'Endorsement Letters')
@section('eyebrow', $user->approver_type === 'vp_research' ? 'VPREI' : 'VPAA')
@section('page_title', 'Endorsement Letters for Review')

@section('content')
<div class="space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold">Endorsement Letters</h2>
        <p class="text-sm text-slate-500 mt-1">
            {{ $user->approver_type === 'vp_research' ? 'Research' : 'Academic' }} endorsements
            submitted by deans for your review.
        </p>
    </div>

    @php
        $pending  = $endorsements->where('status', 'submitted');
        $approved = $endorsements->where('status', 'approved');
        $rejected = $endorsements->where('status', 'rejected');
    @endphp

    {{-- Pending Reviews --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold">Awaiting Your Review</h3>
                <p class="text-xs text-slate-400">{{ $pending->count() }} endorsement(s) pending</p>
            </div>
            @if($pending->count() > 0)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                {{ $pending->count() }} Pending
            </span>
            @endif
        </div>

        @if($pending->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="inbox" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
                <p class="text-sm font-medium text-slate-500">No pending endorsements</p>
                <p class="text-xs text-slate-400 mt-1">You're all caught up.</p>
            </div>
        @else
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left bg-slate-50">
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Event</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Dean</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Staff</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Cost</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Submitted</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($pending as $endorsement)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-slate-800">{{ $endorsement->invitation->event_name }}</p>
                        <p class="text-xs text-slate-400">{{ $endorsement->invitation->formattedDates() }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm text-slate-700">{{ $endorsement->dean->name }}</p>
                        <p class="text-xs text-slate-400">{{ $endorsement->dean->department?->name }}</p>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ $endorsement->staff->count() }} staff</td>
                    <td class="px-4 py-3 text-sm text-slate-700">₱{{ number_format($endorsement->estimated_cost, 2) }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $endorsement->submitted_at?->diffForHumans() }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('endorsement-letters.show', $endorsement) }}"
                           class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-ua-red-600 hover:bg-ua-red-700 rounded-lg">
                            Review
                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Recently Reviewed --}}
    @if($approved->count() + $rejected->count() > 0)
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <h3 class="text-base font-semibold">Recently Reviewed</h3>
            <p class="text-xs text-slate-400">Past decisions you have made</p>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left bg-slate-50">
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Event</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Dean</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Reviewed</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($approved->concat($rejected)->sortByDesc('reviewed_at') as $endorsement)
                <tr>
                    <td class="px-4 py-3">
                        <p class="text-sm text-slate-700">{{ $endorsement->invitation->event_name }}</p>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ $endorsement->dean->name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $endorsement->statusBadgeClass() }}">
                            {{ ucfirst($endorsement->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $endorsement->reviewed_at?->diffForHumans() }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('endorsement-letters.show', $endorsement) }}"
                           class="text-xs text-ua-red-600 hover:underline">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
