@extends('layouts.app')

@section('title', 'Analytics')
@section('eyebrow', 'Administration')
@section('page_title', 'Analytics')

@section('content')
@php
    $maxMonth = max(1, max($requestsPerMonth ?: [0]), max($approvedPerMonth ?: [0]));
    $statusLabels = [
        'draft' => 'Draft', 'submitted' => 'Submitted', 'pending_signature' => 'Awaiting Signature',
        'pending_release' => 'Awaiting Release', 'issued' => 'Issued', 'active' => 'Active',
        'returned' => 'Returned', 'completed' => 'Completed',
    ];
    $statusColors = [
        'draft' => 'bg-slate-300', 'submitted' => 'bg-amber-400', 'pending_signature' => 'bg-orange-400',
        'pending_release' => 'bg-indigo-400', 'issued' => 'bg-emerald-400', 'active' => 'bg-sky-400',
        'returned' => 'bg-blue-400', 'completed' => 'bg-teal-500',
    ];
    $maxDept = max(1, ...($chartDeptCounts ?: [0]));
    $maxDest = max(1, ...($chartDestCounts ?: [0]));
@endphp

<div class="max-w-6xl mx-auto space-y-6">

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @include('partials.stat-card', ['label' => 'Total Travel Orders', 'value' => $totalRequests, 'icon' => 'file-text', 'color' => 'ua-red', 'highlight' => true])
        @include('partials.stat-card', ['label' => 'Released', 'value' => $totalApproved, 'icon' => 'stamp', 'color' => 'emerald'])
        @include('partials.stat-card', ['label' => 'Completed', 'value' => $totalCompleted, 'icon' => 'check-circle-2', 'color' => 'teal'])
        @include('partials.stat-card', ['label' => 'In Progress', 'value' => $totalPending, 'icon' => 'clock', 'color' => 'amber'])
        @include('partials.stat-card', ['label' => 'Release Rate', 'value' => $releaseRate . '%', 'icon' => 'percent', 'color' => 'indigo'])
    </div>

    {{-- Monthly trend --}}
    <section class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
        <div class="flex items-center gap-2 mb-1">
            <i data-lucide="bar-chart-3" class="w-5 h-5 text-ua-red-600"></i>
            <h3 class="font-semibold">Travel Orders — Last 12 Months</h3>
        </div>
        <p class="text-xs text-slate-500 mb-5">Created vs. released per month</p>

        <div class="flex items-center gap-4 mb-3 text-xs">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-ua-red-500"></span> Created</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-400"></span> Released</span>
        </div>

        <div class="flex items-end justify-between gap-1 h-48 border-b border-slate-100">
            @foreach($months as $idx => $month)
            <div class="flex-1 flex flex-col items-center justify-end gap-1 h-full">
                <div class="flex items-end gap-0.5 h-full w-full justify-center">
                    <div class="w-2.5 rounded-t bg-ua-red-500" style="height: {{ round(($requestsPerMonth[$idx] / $maxMonth) * 100) }}%" title="{{ $requestsPerMonth[$idx] }} created"></div>
                    <div class="w-2.5 rounded-t bg-emerald-400" style="height: {{ round(($approvedPerMonth[$idx] / $maxMonth) * 100) }}%" title="{{ $approvedPerMonth[$idx] }} released"></div>
                </div>
                <span class="text-[9px] text-slate-400 whitespace-nowrap">{{ \Illuminate\Support\Str::of($month)->explode(' ')->first() }}</span>
            </div>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Status distribution --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="pie-chart" class="w-5 h-5 text-ua-red-600"></i>
                <h3 class="font-semibold">Status Distribution</h3>
            </div>
            @if(empty($chartStatuses))
                <p class="text-sm text-slate-400 py-6 text-center">No Travel Orders yet.</p>
            @else
                <div class="space-y-3">
                    @foreach($chartStatuses as $status => $count)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-slate-700">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
                            <span class="text-slate-400">{{ $count }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $statusColors[$status] ?? 'bg-slate-400' }}"
                                 style="width: {{ round(($count / max(1, $totalRequests)) * 100) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Travel Orders by department --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="building-2" class="w-5 h-5 text-ua-red-600"></i>
                <h3 class="font-semibold">Travel Orders by Department</h3>
            </div>
            @if(empty($chartDeptLabels))
                <p class="text-sm text-slate-400 py-6 text-center">No data yet.</p>
            @else
                <div class="space-y-3">
                    @foreach($chartDeptLabels as $idx => $label)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-slate-700">{{ $label }}</span>
                            <span class="text-slate-400">{{ $chartDeptCounts[$idx] }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-indigo-400" style="width: {{ round(($chartDeptCounts[$idx] / $maxDept) * 100) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Top destinations --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="map-pin" class="w-5 h-5 text-ua-red-600"></i>
                <h3 class="font-semibold">Top Destinations</h3>
            </div>
            @if(empty($chartDestLabels))
                <p class="text-sm text-slate-400 py-6 text-center">No data yet.</p>
            @else
                <div class="space-y-3">
                    @foreach($chartDestLabels as $idx => $label)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-slate-700 truncate max-w-[70%]">{{ $label ?: '—' }}</span>
                            <span class="text-slate-400">{{ $chartDestCounts[$idx] }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-sky-400" style="width: {{ round(($chartDestCounts[$idx] / $maxDest) * 100) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Endorsement breakdown --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="file-signature" class="w-5 h-5 text-ua-red-600"></i>
                <h3 class="font-semibold">Endorsement Letters</h3>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-center">
                    <p class="text-2xl font-bold text-emerald-700">{{ $endorsementApproved }}</p>
                    <p class="text-xs text-emerald-600 mt-0.5">Approved</p>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 text-center">
                    <p class="text-2xl font-bold text-amber-700">{{ $endorsementPending }}</p>
                    <p class="text-xs text-amber-600 mt-0.5">Under Review</p>
                </div>
                <div class="rounded-xl border border-rose-100 bg-rose-50 p-4 text-center">
                    <p class="text-2xl font-bold text-rose-700">{{ $endorsementRejected }}</p>
                    <p class="text-xs text-rose-600 mt-0.5">Returned</p>
                </div>
            </div>
        </section>
    </div>

    {{-- Top travelers --}}
    <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="flex items-center gap-2 p-5 sm:p-6 border-b border-slate-200">
            <i data-lucide="award" class="w-5 h-5 text-ua-red-600"></i>
            <h3 class="font-semibold">Most Active Travelers</h3>
        </div>
        @if($topTravelers->isEmpty())
            <p class="text-sm text-slate-400 py-8 text-center">No travel activity yet.</p>
        @else
            <table class="w-full text-sm">
                <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium">Traveler</th>
                        <th class="text-left px-5 py-3 font-medium">Department</th>
                        <th class="text-right px-5 py-3 font-medium">Travel Orders</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($topTravelers as $t)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $t->name }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $t->department->abbreviation ?? $t->department->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-ua-red-700">{{ $t->to_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

</div>
@endsection
