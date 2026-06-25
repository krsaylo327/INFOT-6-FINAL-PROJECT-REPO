@extends('layouts.app')

@section('title', 'Submit Expense Report')
@section('eyebrow', 'Post-Travel')
@section('page_title', 'New Expense Report')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('travel-orders.show', $travelOrder) }}" class="hover:text-ua-red-600">Travel Order {{ $travelOrder->to_number ?? '' }}</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium">Expense Report</span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 class="text-base font-semibold mb-1">Start an Expense Report</h2>
        <p class="text-sm text-slate-500 mb-5">
            Travel Order <strong>{{ $travelOrder->to_number ?? 'pending' }}</strong> for <strong>{{ $travelOrder->event_name }}</strong> is complete.
            Click <strong>Start Report</strong> to begin adding itemized receipts (transport, lodging, meals, registration). University policy requires reconciliation within 20 working days of return.
        </p>

        <div class="mb-5 grid sm:grid-cols-2 gap-3 text-sm">
            <div class="p-3 bg-slate-50 rounded-xl">
                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-0.5">Event</p>
                <p class="font-medium text-slate-800">{{ $travelOrder->event_name }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-0.5">Dates</p>
                <p class="font-medium text-slate-800">{{ $travelOrder->formattedDates() }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('expense-reports.store', $travelOrder) }}">
            @csrf
            <div class="flex items-center gap-3">
                <button type="submit"
                        class="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Start Report
                </button>
                <a href="{{ route('travel-orders.show', $travelOrder) }}" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
