@extends('layouts.app')

@section('title', 'Assignments')
@section('eyebrow', 'Review')
@section('page_title', 'Assignments You\'ve Issued')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Travel Assignments</h2>
            <p class="text-sm text-slate-500">Travel requests you've assigned to travelers.</p>
        </div>
        <a href="{{ route('assignments.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-ua-red-600 text-white text-sm font-semibold rounded-xl hover:bg-ua-red-700 shadow-sm">
            <i data-lucide="send" class="w-4 h-4"></i>
            <span>Assign Travel</span>
        </a>
    </div>

    @if($assignments->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 mb-4">
                <i data-lucide="user-plus" class="w-6 h-6"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-1">No assignments yet</h3>
            <p class="text-sm text-slate-500 mb-5">You haven't assigned travel to anyone yet.</p>
            <a href="{{ route('assignments.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-ua-red-600 text-white text-sm font-semibold rounded-xl hover:bg-ua-red-700">
                <i data-lucide="send" class="w-4 h-4"></i>
                <span>Create First Assignment</span>
            </a>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3 font-semibold">Request #</th>
                            <th class="px-6 py-3 font-semibold">Traveler</th>
                            <th class="px-6 py-3 font-semibold">Destination</th>
                            <th class="px-6 py-3 font-semibold">Dates</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($assignments as $tr)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="text-xs font-mono text-slate-600">{{ $tr->request_no }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-ua-red-100 text-ua-red-700 text-xs font-semibold flex items-center justify-center">
                                            {{ strtoupper(substr($tr->user->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-900">{{ $tr->user->name ?? '—' }}</p>
                                            <p class="text-xs text-slate-500">{{ $tr->department->name ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-700">{{ $tr->destination }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $tr->date_from->format('M d') }} – {{ $tr->date_to->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    @include('partials.status-pill', ['status' => $tr->status])
                                    @if($tr->status === 'assigned')
                                        <p class="text-[10px] text-slate-400 mt-1">Awaiting acknowledgement</p>
                                    @elseif($tr->acknowledged_at)
                                        <p class="text-[10px] text-slate-400 mt-1">Ack'd {{ $tr->acknowledged_at->diffForHumans() }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('travel-requests.show', $tr) }}"
                                       class="inline-flex items-center gap-1 text-sm font-medium text-ua-red-700 hover:text-ua-red-800">
                                        View
                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
