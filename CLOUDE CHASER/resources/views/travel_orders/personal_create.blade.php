@extends('layouts.app')

@section('title', 'Request Travel Order')
@section('eyebrow', 'Personal Travel')
@section('page_title', 'Request a Travel Order')

@section('content')
<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
        <a href="{{ route('travel-orders.my') }}" class="hover:text-ua-red-600">My Travel Orders</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-800 font-medium">New Request</span>
    </div>

    <div class="mb-4 flex items-start gap-3 p-4 bg-indigo-50 border border-indigo-200 rounded-xl">
        <i data-lucide="info" class="w-5 h-5 text-indigo-600 mt-0.5 shrink-0"></i>
        <div class="text-sm text-indigo-800">
            <p class="font-semibold">Personal Travel Order</p>
            <p class="text-xs mt-0.5 text-indigo-700">This is your own request. Your dean will be <strong>noted</strong> (not endorsing). The letter will be signed by you.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <form method="POST" action="{{ route('travel-orders.personal.store') }}" class="space-y-5">
            @csrf

            {{-- Event Name --}}
            <div>
                <label for="event_name" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Event / Conference Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="event_name" id="event_name"
                       value="{{ old('event_name') }}"
                       placeholder="e.g. Regional Research Symposium"
                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                @error('event_name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Venue + Destination --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="venue" class="block text-sm font-medium text-slate-700 mb-1.5">Venue <span class="text-rose-500">*</span></label>
                    <input type="text" name="venue" id="venue"
                           value="{{ old('venue') }}"
                           placeholder="e.g. Iloilo Convention Center"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('venue')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="destination" class="block text-sm font-medium text-slate-700 mb-1.5">Destination <span class="text-rose-500">*</span></label>
                    <input type="text" name="destination" id="destination"
                           value="{{ old('destination') }}"
                           placeholder="e.g. Iloilo City"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('destination')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="date_from" class="block text-sm font-medium text-slate-700 mb-1.5">Date From <span class="text-rose-500">*</span></label>
                    <input type="date" name="date_from" id="date_from"
                           value="{{ old('date_from') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('date_from')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-slate-700 mb-1.5">Date To <span class="text-rose-500">*</span></label>
                    <input type="date" name="date_to" id="date_to"
                           value="{{ old('date_to') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400">
                    @error('date_to')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Purpose --}}
            <div>
                <label for="purpose" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Purpose of Travel <span class="text-rose-500">*</span>
                </label>
                <textarea name="purpose" id="purpose" rows="3"
                          placeholder="Briefly describe the purpose of this travel (min. 20 characters)"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-400 resize-none">{{ old('purpose') }}</textarea>
                @error('purpose')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Noted By (dean, auto-selected) --}}
            <div class="border border-slate-100 bg-slate-50 rounded-xl p-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Noted By</p>
                @if($dean)
                    <input type="hidden" name="noted_by" value="{{ $dean->id }}">
                    <p class="text-sm font-medium text-slate-800">{{ $dean->name }}</p>
                    <p class="text-xs text-slate-400">{{ $dean->requested_position ?? 'Dean' }} — {{ $dean->department?->name }}</p>
                    <p class="text-xs text-slate-400 mt-1">Auto-selected based on your department. Dean's name will appear as "Noted by" on the letter.</p>
                @else
                    <p class="text-sm text-slate-500 italic">No dean found for your department. The letter will not have a "Noted by" section.</p>
                @endif
            </div>

            {{-- Receipt / TO Timing --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Travel Order Receipt Timing <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 border border-slate-200 rounded-xl p-3.5 cursor-pointer hover:bg-slate-50 has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50">
                        <input type="radio" name="receipt_timing" value="before_travel" class="mt-0.5 accent-emerald-600"
                               {{ old('receipt_timing', 'before_travel') === 'before_travel' ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-slate-800">Before Travel</p>
                            <p class="text-xs text-slate-500">TO received prior to departure (standard)</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 border border-slate-200 rounded-xl p-3.5 cursor-pointer hover:bg-slate-50 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                        <input type="radio" name="receipt_timing" value="after_travel" class="mt-0.5 accent-amber-600"
                               {{ old('receipt_timing') === 'after_travel' ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-slate-800">After Travel</p>
                            <p class="text-xs text-slate-500">TO received upon return (urgent travel)</p>
                        </div>
                    </label>
                </div>
                @error('receipt_timing')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" name="action" value="draft"
                        class="flex items-center gap-2 px-5 py-2.5 border border-slate-300 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save as Draft
                </button>
                <button type="submit" name="action" value="submit"
                        class="flex items-center gap-2 px-5 py-2.5 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-medium rounded-xl">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Submit to President
                </button>
                <a href="{{ route('travel-orders.my') }}" class="text-sm text-slate-500 hover:text-slate-700 ml-auto">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
