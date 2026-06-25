@extends('layouts.app')

@section('title', 'Assign Travel')
@section('eyebrow', 'Review')
@section('page_title', 'Assign Travel to a Traveler')

@section('content')
    <div class="max-w-3xl">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-900">Assign Travel</h2>
            <p class="text-sm text-slate-500">
                Pick a traveler and fill in the trip details. They'll be asked to acknowledge the assignment before it enters the approval chain.
            </p>
        </div>

        @if($travelers->isEmpty())
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 flex items-start gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 mt-0.5"></i>
                <div class="text-sm text-amber-800">
                    <p class="font-semibold mb-1">No travelers available</p>
                    <p>There are no users with the <strong>traveler</strong> role yet. Create one first or ask your admin.</p>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('assignments.store') }}" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
                @csrf

                {{-- Traveler picker --}}
                <div>
                    <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-2">
                        <i data-lucide="user" class="w-3.5 h-3.5 text-ua-red-600"></i>
                        Traveler
                    </label>
                    <select name="user_id" required
                            class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-500 focus:border-ua-red-500">
                        <option value="">— Select a traveler —</option>
                        @foreach($travelers as $traveler)
                            <option value="{{ $traveler->id }}"
                                {{ old('user_id') == $traveler->id ? 'selected' : '' }}>
                                {{ $traveler->name }}
                                @if($traveler->department)
                                    — {{ $traveler->department->name }}
                                @endif
                                ({{ $traveler->email }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-1">The traveler's department will be used automatically for the trip.</p>
                </div>

                {{-- Category --}}
                <div>
                    <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-2">
                        <i data-lucide="layers" class="w-3.5 h-3.5 text-ua-red-600"></i>
                        Travel Category
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative flex items-start gap-3 p-3 border rounded-xl cursor-pointer has-[:checked]:border-ua-red-400 has-[:checked]:bg-ua-red-50 border-slate-200 hover:bg-slate-50 transition-colors">
                            <input type="radio" name="category" value="academic" {{ old('category') === 'academic' ? 'checked' : '' }} class="mt-0.5 accent-ua-red-600" required>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Academic</p>
                                <p class="text-xs text-slate-500">Conferences, trainings, seminars for instruction/faculty development</p>
                            </div>
                        </label>
                        <label class="relative flex items-start gap-3 p-3 border rounded-xl cursor-pointer has-[:checked]:border-ua-red-400 has-[:checked]:bg-ua-red-50 border-slate-200 hover:bg-slate-50 transition-colors">
                            <input type="radio" name="category" value="research" {{ old('category') === 'research' ? 'checked' : '' }} class="mt-0.5 accent-ua-red-600" required>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Research</p>
                                <p class="text-xs text-slate-500">Research activities, publications, and extension programs</p>
                            </div>
                        </label>
                    </div>
                    @error('category')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Destination --}}
                <div>
                    <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-ua-red-600"></i>
                        Destination
                    </label>
                    <input type="text" name="destination" value="{{ old('destination') }}" required
                           placeholder="e.g., Manila, Philippines"
                           class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-500 focus:border-ua-red-500">
                </div>

                {{-- Purpose --}}
                <div>
                    <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-2">
                        <i data-lucide="file-text" class="w-3.5 h-3.5 text-ua-red-600"></i>
                        Purpose
                    </label>
                    <textarea name="purpose" rows="3" required
                              placeholder="e.g., Represent the university in the CHED regional planning meeting."
                              class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-500 focus:border-ua-red-500">{{ old('purpose') }}</textarea>
                </div>

                {{-- Dates --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-2">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-ua-red-600"></i>
                            Date From
                        </label>
                        <input type="date" name="date_from" value="{{ old('date_from') }}" required
                               class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-500 focus:border-ua-red-500">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-2">
                            <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-ua-red-600"></i>
                            Date To
                        </label>
                        <input type="date" name="date_to" value="{{ old('date_to') }}" required
                               class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-500 focus:border-ua-red-500">
                    </div>
                </div>

                {{-- Estimated cost --}}
                <div>
                    <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-2">
                        <i data-lucide="circle-dollar-sign" class="w-3.5 h-3.5 text-ua-red-600"></i>
                        Estimated Cost (PHP)
                    </label>
                    <input type="number" step="0.01" min="0" name="estimated_cost" value="{{ old('estimated_cost', 0) }}" required
                           class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-500 focus:border-ua-red-500">
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-ua-red-600 text-white text-sm font-semibold rounded-xl hover:bg-ua-red-700 shadow-sm">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Send Assignment</span>
                    </button>
                    <a href="{{ route('assignments.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">
                        Cancel
                    </a>
                </div>
            </form>
        @endif
    </div>
@endsection
