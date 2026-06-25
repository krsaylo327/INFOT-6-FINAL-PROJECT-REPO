@extends('layouts.app')

@section('title', 'Expense Report')
@section('eyebrow', 'Expense Report')
@section('page_title', 'Expense Report — ' . ($expenseReport->travelOrder->to_number ?? 'Pending'))

@section('content')
@php
    $isPresident = $user->role === 'dean' && $user->department?->abbreviation === 'PRES';
    $isReviewer = $user->role === 'admin' || $isPresident;
    $isOwner = $expenseReport->submitted_by === $user->id
        || $expenseReport->travelOrder->traveler_id === $user->id
        || $expenseReport->travelOrder->dean_id === $user->id;
    $canEdit = ($expenseReport->isDraft() || $expenseReport->isQueried()) && $isOwner;
@endphp

<div class="max-w-4xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('travel-orders.show', $expenseReport->travelOrder) }}" class="hover:text-ua-red-600">
            Travel Order {{ $expenseReport->travelOrder->to_number ?? '' }}
        </a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium">Expense Report</span>
    </div>

    {{-- Header --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-lg font-semibold">Expense Report</h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $expenseReport->statusBadgeClass() }}">
                        {{ ucfirst($expenseReport->status) }}
                    </span>
                </div>
                <p class="text-sm text-slate-600">{{ $expenseReport->travelOrder->event_name }}</p>
                <p class="text-sm text-slate-400">Submitted by <strong>{{ $expenseReport->submitter->name }}</strong></p>
                @if($expenseReport->submitted_at)
                    <p class="text-xs text-slate-400">Submitted {{ $expenseReport->submitted_at->format('F j, Y, g:i A') }}</p>
                @endif
            </div>

            <div class="text-right">
                <p class="text-xs text-slate-400 uppercase tracking-wide">Total Amount</p>
                <p class="text-2xl font-semibold text-emerald-700">₱{{ number_format($expenseReport->total_amount, 2) }}</p>
                <p class="text-xs text-slate-400">{{ $expenseReport->items->count() }} item(s)</p>
            </div>
        </div>

        @if($expenseReport->remarks)
            <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-amber-700 mb-1">Reviewer Remarks</p>
                {{ $expenseReport->remarks }}
            </div>
        @endif
    </div>

    {{-- Add item form --}}
    @if($canEdit)
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h3 class="text-base font-semibold mb-3">Add Expense Item</h3>
        <form method="POST" action="{{ route('expense-reports.items.store', $expenseReport) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Description <span class="text-rose-500">*</span></label>
                    <input type="text" name="description" required maxlength="255"
                           value="{{ old('description') }}"
                           placeholder="e.g. Round-trip airfare Iloilo–Manila"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('description')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Amount (₱) <span class="text-rose-500">*</span></label>
                    <input type="number" name="amount" required step="0.01" min="0" max="999999.99"
                           value="{{ old('amount') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('amount')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Expense Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="expense_date" required
                           value="{{ old('expense_date') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('expense_date')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Category <span class="text-rose-500">*</span></label>
                    <select name="category" required
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                        <option value="transport" {{ old('category') === 'transport' ? 'selected' : '' }}>Transport</option>
                        <option value="lodging" {{ old('category') === 'lodging' ? 'selected' : '' }}>Lodging</option>
                        <option value="meals" {{ old('category') === 'meals' ? 'selected' : '' }}>Meals</option>
                        <option value="registration" {{ old('category') === 'registration' ? 'selected' : '' }}>Registration Fee</option>
                        <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Receipt <span class="text-slate-400 text-xs font-normal">(optional, PDF/JPG/PNG, 5MB max)</span></label>
                    <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2">
                    @error('receipt')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Add Item
            </button>
        </form>
    </div>
    @endif

    {{-- Items list --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <h3 class="text-base font-semibold">Expense Items</h3>
            <p class="text-xs text-slate-400">{{ $expenseReport->items->count() }} line item(s)</p>
        </div>

        @if($expenseReport->items->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="receipt" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
                <p class="text-sm font-medium text-slate-500">No expense items yet</p>
                <p class="text-xs text-slate-400 mt-1">Add itemized expenses using the form above</p>
            </div>
        @else
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left bg-slate-50">
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Description</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Category</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Date</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide text-right">Amount</th>
                    <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Receipt</th>
                    @if($canEdit)<th class="px-4 py-3"></th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($expenseReport->items as $item)
                <tr>
                    <td class="px-4 py-3 text-slate-800">{{ $item->description }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                            <i data-lucide="{{ $item->categoryIcon() }}" class="w-3 h-3"></i>
                            {{ $item->categoryLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $item->expense_date->format('M j, Y') }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-800">₱{{ number_format($item->amount, 2) }}</td>
                    <td class="px-4 py-3">
                        @if($item->hasReceipt())
                            <a href="{{ route('expense-reports.items.receipt', $item) }}" target="_blank"
                               class="text-xs text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-1">
                                <i data-lucide="eye" class="w-3 h-3"></i> View
                            </a>
                        @else
                            <span class="text-xs text-slate-300">no receipt</span>
                        @endif
                    </td>
                    @if($canEdit)
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('expense-reports.items.destroy', $item) }}"
                              onsubmit="return confirm('Remove this expense item?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-rose-600 hover:text-rose-700">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</td>
                    <td class="px-4 py-3 text-right text-base font-bold text-emerald-700">₱{{ number_format($expenseReport->total_amount, 2) }}</td>
                    <td colspan="{{ $canEdit ? 2 : 1 }}"></td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>

    {{-- Actions --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        @if($canEdit && $expenseReport->items->count() > 0)
            <form method="POST" action="{{ route('expense-reports.submit', $expenseReport) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Submit for Review
                </button>
                <p class="text-xs text-slate-400 mt-2">Once submitted, items cannot be edited until the reviewer queries the report.</p>
            </form>
        @elseif($isReviewer && ($expenseReport->isSubmitted() || $expenseReport->isQueried()))
            <h3 class="text-base font-semibold mb-3">Review Decision</h3>
            <form method="POST" action="{{ route('expense-reports.review', $expenseReport) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Remarks (optional)</label>
                    <textarea name="remarks" rows="3" maxlength="1000"
                              placeholder="Notes for the traveler (e.g. missing receipt for item 3, exceeds per-diem)"
                              class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ $expenseReport->remarks }}</textarea>
                </div>
                        <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        <i data-lucide="key-round" class="inline w-3.5 h-3.5 mr-1 text-amber-500"></i>
                        Security Key <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" name="security_key" required
                           placeholder="Enter your account password to confirm"
                           class="w-full max-w-sm px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400 @error('security_key') border-rose-400 @enderror">
                    @error('security_key')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-slate-400 flex items-center gap-1">
                        <i data-lucide="info" class="w-3 h-3"></i>
                        A digital signature will be embedded if you have one on file. Only approvals are signed — queries are not.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" name="decision" value="approved"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        Approve
                    </button>
                    <button type="submit" name="decision" value="queried"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-xl">
                        <i data-lucide="help-circle" class="w-4 h-4"></i>
                        Query / Return for Clarification
                    </button>
                </div>
            </form>
        @elseif($expenseReport->isApproved())
            @php $reviewSig = $expenseReport->reviewSignature(); @endphp
            <div class="flex items-center gap-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl mb-4">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                <div>
                    <p class="text-sm font-semibold text-emerald-800">Approved</p>
                    <p class="text-xs text-emerald-700">
                        Reviewed by {{ $expenseReport->reviewer?->name ?? 'admin' }} on {{ $expenseReport->reviewed_at?->format('F j, Y, g:i A') }}
                    </p>
                </div>
            </div>

            @if($reviewSig)
            <div class="border border-emerald-200 rounded-xl overflow-hidden">
                <div class="bg-emerald-600 px-4 py-3 flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-4 h-4 text-white"></i>
                    <p class="text-sm font-semibold text-white">Digitally Signed by Reviewer</p>
                </div>
                <div class="p-4 flex items-start gap-4 flex-wrap">
                    <div class="border border-slate-200 rounded-lg p-2 bg-white shrink-0">
                        <img src="{{ route('signatures.verify.image', $reviewSig->verification_code) }}"
                             alt="Reviewer signature" class="h-14 w-auto max-w-[160px] object-contain">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900">{{ $reviewSig->signer_name_snapshot }}</p>
                        <p class="text-xs text-slate-500">{{ $reviewSig->signer_position_snapshot }}</p>
                        <p class="text-xs text-slate-400 mt-1">Signed {{ $reviewSig->signed_at->format('F j, Y, g:i A') }}</p>
                        <a href="{{ route('signatures.verify', $reviewSig->verification_code) }}" target="_blank"
                           class="inline-flex items-center gap-1 text-xs text-emerald-600 hover:text-emerald-700 font-medium mt-2">
                            <i data-lucide="external-link" class="w-3 h-3"></i>
                            Verify signature · {{ $reviewSig->verification_code }}
                        </a>
                    </div>
                    <div class="text-center shrink-0">
                        <img src="{{ route('signatures.verify.qr', $reviewSig->verification_code) }}"
                             alt="QR" class="w-16 h-16">
                        <p class="text-[10px] text-slate-400 mt-1">Scan to verify</p>
                    </div>
                </div>
            </div>
            @endif
        @endif
    </div>
</div>
@endsection
