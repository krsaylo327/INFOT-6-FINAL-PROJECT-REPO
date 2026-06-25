@extends('layouts.app')

@section('title', 'Expense Reports')
@section('eyebrow', 'Administration')
@section('page_title', 'Expense Reports for Review')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <div>
        <h2 class="text-lg font-semibold">Expense Reports</h2>
        <p class="text-sm text-slate-500">Submitted post-travel expense reconciliations awaiting review</p>
    </div>

    @if($reports->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <i data-lucide="receipt" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
            <p class="text-sm font-medium text-slate-500">No expense reports submitted yet</p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left bg-slate-50">
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">TO #</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Submitter</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Event</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Submitted</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide text-right">Total</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($reports as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $r->travelOrder->to_number ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <p class="text-slate-800">{{ $r->submitter->name }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600 truncate max-w-[220px]">{{ $r->travelOrder->event_name }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $r->submitted_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">₱{{ number_format($r->total_amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $r->statusBadgeClass() }}">
                                {{ ucfirst($r->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('expense-reports.show', $r) }}"
                               class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">Review →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
