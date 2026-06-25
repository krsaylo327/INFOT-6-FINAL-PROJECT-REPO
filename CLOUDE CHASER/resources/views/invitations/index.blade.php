@extends('layouts.app')

@section('title', 'Sent Invitations')
@section('eyebrow', "President's Office")
@section('page_title', 'Forwarded Invitations')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold">Forwarded Invitations</h2>
            <p class="text-sm text-slate-500">External invitations forwarded to college deans</p>
        </div>
    </div>

    @if($invitations->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <i data-lucide="mail" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
            <p class="text-sm font-medium text-slate-500">No invitations forwarded yet</p>
            <p class="text-xs text-slate-400 mt-1">Open an invitation from your <strong>Inbox</strong> and forward it to a dean to get started</p>
            <a href="{{ route('received-invitations.index') }}"
               class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
                <i data-lucide="inbox" class="w-4 h-4"></i>
                Go to Inbox
            </a>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left">
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Event</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Assigned Dean</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Type</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Dates</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($invitations as $inv)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800 truncate max-w-[220px]">{{ $inv->event_name }}</p>
                            <p class="text-xs text-slate-400">{{ $inv->destination ?? 'Destination TBD' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-slate-700">{{ $inv->assignedDean->name }}</p>
                            <p class="text-xs text-slate-400">
                                @if($inv->assignedDean->department)
                                    {{ $inv->assignedDean->department->abbreviation }}{{ $inv->assignedDean->department->name && $inv->assignedDean->department->name !== $inv->assignedDean->department->abbreviation ? ' — ' . $inv->assignedDean->department->name : '' }}
                                @else
                                    —
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                {{ $inv->type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                                {{ ucfirst($inv->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $inv->formattedDates() }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusInfo = match(true) {
                                    (bool) $inv->travelOrder => ['icon' => 'check-circle-2', 'label' => 'TO Created',         'cls' => 'bg-emerald-100 text-emerald-700'],
                                    $inv->status === 'endorsed' => ['icon' => 'users',         'label' => 'Endorsed',           'cls' => 'bg-indigo-100 text-indigo-700'],
                                    $inv->status === 'accepted' => ['icon' => 'check',         'label' => 'Accepted',           'cls' => 'bg-sky-100 text-sky-700'],
                                    $inv->status === 'rejected' => ['icon' => 'x-circle',      'label' => 'Declined',           'cls' => 'bg-rose-100 text-rose-700'],
                                    default                     => ['icon' => 'clock',         'label' => 'Awaiting Response',  'cls' => 'bg-amber-100 text-amber-700'],
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusInfo['cls'] }}">
                                <i data-lucide="{{ $statusInfo['icon'] }}" class="w-3 h-3"></i>
                                {{ $statusInfo['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('invitations.show', $inv) }}"
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
