@extends('layouts.app')

@section('title', 'My Travel Requests')
@section('eyebrow', 'Travel')
@section('page_title', 'My Travel Requests')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-slate-900">My Travel Requests</h2>
            <p class="text-sm text-slate-500">All travel requests you've submitted.</p>
        </div>
        <a href="{{ route('travel-requests.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-xl text-sm font-semibold shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            New Request
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        @if($requests->isEmpty())
            <div class="text-center py-16 px-6">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="plane" class="w-8 h-8 text-slate-400"></i>
                </div>
                <h3 class="font-semibold text-slate-900 mb-1">No travel requests yet</h3>
                <p class="text-sm text-slate-500 mb-5">Create your first request to get started.</p>
                <a href="{{ route('travel-requests.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-xl text-sm font-semibold">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Create Request
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
                        <tr>
                            <th class="text-left px-5 py-3 font-medium">Request No</th>
                            <th class="text-left px-5 py-3 font-medium">Destination</th>
                            <th class="text-left px-5 py-3 font-medium">Dates</th>
                            <th class="text-left px-5 py-3 font-medium">Cost</th>
                            <th class="text-left px-5 py-3 font-medium">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($requests as $request)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-mono text-xs">
                                    {{ $request->request_no }}
                                    @if($request->type === 'assigned')
                                        <span class="ml-1 inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[9px] font-semibold uppercase tracking-wider">
                                            <i data-lucide="user-plus" class="w-2.5 h-2.5"></i>
                                            Assigned
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-medium">{{ $request->destination }}</td>
                                <td class="px-5 py-3 text-slate-600">
                                    {{ $request->date_from->format('M d') }} – {{ $request->date_to->format('M d, Y') }}
                                </td>
                                <td class="px-5 py-3 text-slate-600">
                                    ₱{{ number_format($request->estimated_cost, 2) }}
                                </td>
                                <td class="px-5 py-3">
                                    @include('partials.status-pill', ['status' => $request->status])
                                </td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('travel-requests.show', $request) }}"
                                       class="inline-flex items-center gap-1 text-xs font-semibold text-ua-red-600 hover:text-ua-red-700">
                                        View <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
