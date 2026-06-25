@extends('layouts.app')

@section('title', 'Travel Orders')
@section('eyebrow', 'Dean')
@section('page_title', 'Travel Orders')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold">My Travel Orders</h2>
            <p class="text-sm text-slate-500">Endorsement letters and Travel Orders you have created</p>
        </div>
        <a href="{{ route('travel-orders.create') }}"
           class="flex items-center gap-2 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
            <i data-lucide="plus" class="w-4 h-4"></i>
            New Travel Order
        </a>
    </div>

    @if($travelOrders->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <i data-lucide="file-text" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
            <p class="text-sm font-medium text-slate-500">No travel orders yet</p>
            <p class="text-xs text-slate-400 mt-1">Create a travel order to get started</p>
            <a href="{{ route('travel-orders.create') }}"
               class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Create Travel Order
            </a>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left">
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">TO No. / Status</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Traveler</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Event / Destination</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Type</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Dates</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($travelOrders as $to)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            @if($to->to_number)
                                <p class="font-semibold text-slate-800 text-xs">{{ $to->to_number }}</p>
                            @endif
                            @php
                                $badge = match($to->status) {
                                    'draft'             => 'bg-slate-100 text-slate-600',
                                    'submitted'         => 'bg-amber-100 text-amber-700',
                                    'pending_signature' => 'bg-orange-100 text-orange-700',
                                    'pending_release'   => 'bg-indigo-100 text-indigo-700',
                                    'issued'            => 'bg-emerald-100 text-emerald-700',
                                    'active'            => 'bg-sky-100 text-sky-700',
                                    'returned'          => 'bg-blue-100 text-blue-700',
                                    'completed'         => 'bg-teal-100 text-teal-700',
                                    default             => 'bg-slate-100 text-slate-600',
                                };
                                $statusLabel = match($to->status) {
                                    'pending_signature' => 'Awaiting Signature',
                                    'pending_release'   => 'Awaiting Release',
                                    default             => ucfirst(str_replace('_', ' ', $to->status)),
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badge }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800">{{ $to->traveler->name }}</p>
                            <p class="text-xs text-slate-400">{{ $to->department->abbreviation ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800 truncate max-w-[200px]">{{ $to->event_name }}</p>
                            <p class="text-xs text-slate-400">{{ $to->destination }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                {{ $to->type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                                {{ ucfirst($to->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                            {{ $to->date_from->format('M j') }} – {{ $to->date_to->format('M j, Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('travel-orders.show', $to) }}"
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
