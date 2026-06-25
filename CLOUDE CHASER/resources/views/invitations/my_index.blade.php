@extends('layouts.app')

@section('title', 'My Event Invitations')
@section('eyebrow', 'Invitations')
@section('page_title', 'My Event Invitations')

@section('content')

<div class="mb-4">
    <p class="text-sm text-slate-500">
        Events you have been assigned to travel for, based on external invitations received by the university.
    </p>
</div>

@forelse($invitations as $inv)
@php
    $to = $inv->travelOrder;
    $statusBadge = match($to?->status) {
        'draft'      => 'bg-slate-100 text-slate-600',
        'submitted'  => 'bg-amber-100 text-amber-700',
        'issued'     => 'bg-emerald-100 text-emerald-700',
        'active'     => 'bg-sky-100 text-sky-700',
        'completed'  => 'bg-teal-100 text-teal-700',
        default      => 'bg-slate-100 text-slate-600',
    };
@endphp
<div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 mb-4">
    <div class="flex items-start justify-between gap-3 mb-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full
                    {{ $inv->type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                    {{ ucfirst($inv->type) }}
                </span>
                @if($to)
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold {{ $statusBadge }}">
                        TO: {{ ucfirst($to->status) }}
                        @if($to->to_number)· {{ $to->to_number }}@endif
                    </span>
                @endif
            </div>
            <h3 class="text-lg font-bold text-slate-900">{{ $inv->event_name }}</h3>
            <p class="text-sm text-slate-500 mt-0.5">
                <i data-lucide="map-pin" class="inline w-3.5 h-3.5 mr-1"></i>{{ $inv->venue }}, {{ $inv->destination }}
            </p>
        </div>
        @if($to)
            <a href="{{ route('travel-orders.show', $to) }}"
               class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                View Travel Order
            </a>
        @endif
    </div>

    <div class="grid sm:grid-cols-3 gap-4 text-sm mb-4">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 mb-1">Dates</p>
            <p class="font-medium">{{ $inv->formattedDates() }}</p>
        </div>
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 mb-1">Forwarded By</p>
            <p class="font-medium">{{ $inv->issuer?->name ?? '—' }}</p>
        </div>
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 mb-1">Travel Officer</p>
            <p class="font-medium">{{ $inv->assignedDean?->name ?? '—' }}</p>
        </div>
    </div>

    @if($inv->details)
        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
            <p class="text-xs text-slate-600 leading-relaxed line-clamp-3">{{ $inv->details }}</p>
        </div>
    @endif
</div>
@empty
<div class="bg-white rounded-2xl border border-slate-200 py-16 text-center">
    <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="mail-x" class="w-7 h-7 text-slate-300"></i>
    </div>
    <h3 class="font-semibold text-slate-700 mb-1">No invitations yet</h3>
    <p class="text-sm text-slate-400">You haven't been assigned to any externally invited events.</p>
</div>
@endforelse

@endsection
