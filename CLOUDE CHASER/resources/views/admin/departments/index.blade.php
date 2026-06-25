@extends('layouts.app')

@section('title', 'Departments')
@section('eyebrow', 'Administration')
@section('page_title', 'Departments')

@section('content')

{{-- Header row --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-bold text-slate-900">All Departments</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $departments->count() }} department{{ $departments->count() !== 1 ? 's' : '' }} · {{ $departments->sum('users_count') }} assigned users</p>
    </div>
    @if($unassignedCount > 0)
        <div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-xl flex items-center gap-1.5">
            <i data-lucide="alert-circle" class="w-4 h-4"></i>
            {{ $unassignedCount }} user{{ $unassignedCount !== 1 ? 's' : '' }} without a department
        </div>
    @endif
</div>

@if($departments->isEmpty())
    <div class="text-center py-20 bg-white rounded-2xl border border-slate-200">
        <i data-lucide="building-2" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
        <p class="text-slate-500">No departments found.</p>
    </div>
@else
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($departments as $dept)
            <a href="{{ route('admin.departments.show', $dept) }}"
               class="group bg-white rounded-2xl border border-slate-200 p-5 hover:border-indigo-300 hover:shadow-md transition-all flex flex-col gap-4">

                {{-- Header --}}
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        @if($dept->abbreviation)
                            <div class="w-11 h-11 rounded-xl bg-indigo-600 flex items-center justify-center text-white text-sm font-bold tracking-tight shrink-0">
                                {{ $dept->abbreviation }}
                            </div>
                        @else
                            <div class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                                <i data-lucide="building-2" class="w-5 h-5 text-slate-400"></i>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 leading-tight group-hover:text-indigo-700 truncate">{{ $dept->name }}</p>
                            @if($dept->abbreviation)
                                <p class="text-xs text-slate-400 mt-0.5">{{ $dept->abbreviation }}</p>
                            @endif
                        </div>
                    </div>
                    <i data-lucide="arrow-right" class="w-4 h-4 text-slate-300 group-hover:text-indigo-500 shrink-0 mt-1 transition-colors"></i>
                </div>

                {{-- User count --}}
                <div class="flex items-end justify-between">
                    <div>
                        <p class="text-3xl font-bold text-slate-900">{{ $dept->users_count }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $dept->users_count === 1 ? 'member' : 'members' }}</p>
                    </div>
                    @if($dept->pending_count > 0)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-medium">
                            {{ $dept->pending_count }} pending
                        </span>
                    @endif
                </div>

                {{-- Role breakdown --}}
                <div class="flex items-center gap-2 pt-3 border-t border-slate-100 text-xs text-slate-500">
                    @if($dept->approver_count > 0)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-medium">
                            <i data-lucide="user-check" class="w-3 h-3"></i>
                            {{ $dept->approver_count }} approver{{ $dept->approver_count !== 1 ? 's' : '' }}
                        </span>
                    @endif
                    @if($dept->traveler_count > 0)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-medium">
                            <i data-lucide="plane" class="w-3 h-3"></i>
                            {{ $dept->traveler_count }} traveler{{ $dept->traveler_count !== 1 ? 's' : '' }}
                        </span>
                    @endif
                    @if($dept->approver_count === 0 && $dept->traveler_count === 0)
                        <span class="text-slate-400 italic">No members yet</span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endif

@endsection
