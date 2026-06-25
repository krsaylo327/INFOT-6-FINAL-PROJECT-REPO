@extends('layouts.app')

@section('title', 'My Endorsements')
@section('eyebrow', 'Endorsements')
@section('page_title', 'My Endorsements')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <div>
        <h2 class="text-lg font-semibold">My Endorsements</h2>
        <p class="text-sm text-slate-500">Events your Dean has endorsed you to attend</p>
    </div>


    @if($endorsements->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <i data-lucide="award" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
            <p class="text-sm font-medium text-slate-500">No endorsements yet</p>
            <p class="text-xs text-slate-400 mt-1">When your Dean endorses you for an event, it will show up here.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($endorsements as $endorsement)
            @php
                $statusMap = [
                    'draft'     => ['label' => 'Being Prepared', 'class' => 'bg-slate-100 text-slate-600', 'icon' => 'file-pen', 'note' => 'Your Dean is still preparing this endorsement.'],
                    'submitted' => ['label' => 'Under Review',   'class' => 'bg-amber-100 text-amber-700', 'icon' => 'clock', 'note' => 'Waiting for ' . $endorsement->reviewerLabel() . ' to review.'],
                    'approved'  => ['label' => 'Approved',       'class' => 'bg-emerald-100 text-emerald-700', 'icon' => 'check-circle-2', 'note' => 'Approved! Your Travel Order is being processed.'],
                    'rejected'  => ['label' => 'Returned',       'class' => 'bg-rose-100 text-rose-700', 'icon' => 'x-circle', 'note' => 'Returned for revision by the reviewer.'],
                ];
                $s = $statusMap[$endorsement->status] ?? $statusMap['draft'];
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $s['class'] }}">
                                <i data-lucide="{{ $s['icon'] }}" class="w-3 h-3"></i> {{ $s['label'] }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                {{ $endorsement->category === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                                {{ ucfirst($endorsement->category) }}
                            </span>
                        </div>
                        <p class="text-base font-semibold text-slate-900 truncate">{{ $endorsement->invitation->event_name }}</p>
                        <p class="text-xs text-slate-600 mt-0.5">
                            <i data-lucide="calendar" class="inline w-3 h-3"></i>
                            {{ $endorsement->invitation->formattedDates() }}
                            <span class="mx-1 text-slate-300">|</span>
                            <i data-lucide="map-pin" class="inline w-3 h-3"></i>
                            {{ $endorsement->invitation->destination ?? $endorsement->invitation->venue }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1">
                            Endorsed by <span class="font-medium text-slate-700">{{ $endorsement->dean->name }}</span>
                        </p>
                        <p class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                            <i data-lucide="info" class="w-3.5 h-3.5 text-slate-400"></i>
                            {{ $s['note'] }}
                        </p>
                    </div>

                    <div class="flex flex-col items-end gap-2 shrink-0">
                        @if($endorsement->travelOrder)
                            <a href="{{ route('travel-orders.show', $endorsement->travelOrder) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                View Travel Order
                            </a>
                            @if($endorsement->travelOrder->to_number)
                                <span class="text-[11px] font-mono text-ua-red-700">{{ $endorsement->travelOrder->to_number }}</span>
                            @endif
                        @endif
                        <a href="{{ route('endorsement-letters.show', $endorsement) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
