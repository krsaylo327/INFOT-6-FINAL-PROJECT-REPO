@extends('layouts.app')

@section('title', 'My Endorsement Letters')
@section('eyebrow', 'Dean')
@section('page_title', 'My Endorsement Letters')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold">My Endorsement Letters</h2>
        <p class="text-sm text-slate-500 mt-1">Endorsement letters you have created for invitations you cannot personally attend.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        @if($endorsements->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="file-x" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
                <p class="text-sm font-medium text-slate-500">No endorsement letters yet</p>
                <p class="text-xs text-slate-400 mt-1">When you endorse staff for an invitation, the letter will appear here.</p>
            </div>
        @else
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left bg-slate-50">
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Event</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Category</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Staff</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Reviewer</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($endorsements as $endorsement)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-slate-800">{{ $endorsement->invitation->event_name }}</p>
                        <p class="text-xs text-slate-400">{{ $endorsement->created_at->diffForHumans() }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                            {{ $endorsement->category === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                            {{ ucfirst($endorsement->category) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ $endorsement->staff->count() }} staff</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $endorsement->statusBadgeClass() }}">
                            {{ ucfirst($endorsement->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ $endorsement->reviewer?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('endorsement-letters.show', $endorsement) }}"
                           class="text-xs text-ua-red-600 hover:underline">View →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
