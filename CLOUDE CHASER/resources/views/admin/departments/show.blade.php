@extends('layouts.app')

@section('title', $department->name . ' — Department')
@section('eyebrow', 'Departments')
@section('page_title', $department->name)

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-1.5 text-xs text-slate-500 mb-6">
    <a href="{{ route('admin.departments.index') }}" class="hover:text-ua-red-600 font-medium">Departments</a>
    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
    <span class="text-slate-800 font-semibold">{{ $department->name }}</span>
</nav>

{{-- Department header --}}
<div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex items-center gap-4">
            @if($department->abbreviation)
                <div class="w-14 h-14 rounded-2xl bg-indigo-600 flex items-center justify-center text-white text-lg font-bold shrink-0">
                    {{ $department->abbreviation }}
                </div>
            @else
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center shrink-0">
                    <i data-lucide="building-2" class="w-7 h-7 text-slate-400"></i>
                </div>
            @endif
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $department->name }}</h2>
                @if($department->abbreviation)
                    <p class="text-sm text-slate-500">{{ $department->abbreviation }}</p>
                @endif
            </div>
        </div>

        {{-- Stat pills --}}
        <div class="sm:ml-auto flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-sm font-medium">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                {{ $stats['total'] }} total
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-medium">
                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                {{ $stats['active'] }} active
            </span>
            @if($stats['pending'] > 0)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 text-sm font-medium">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    {{ $stats['pending'] }} pending
                </span>
            @endif
            @if($stats['approver'] > 0)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 text-sm font-medium">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    {{ $stats['approver'] }} approver{{ $stats['approver'] !== 1 ? 's' : '' }}
                </span>
            @endif
        </div>
    </div>
</div>

{{-- Members table --}}
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-200">
        <div class="flex items-center gap-2">
            <i data-lucide="users" class="w-5 h-5 text-slate-600"></i>
            <h3 class="font-semibold">Members</h3>
        </div>
        <span class="text-xs text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">{{ $stats['total'] }} {{ $stats['total'] === 1 ? 'person' : 'people' }}</span>
    </div>

    @if($users->isEmpty())
        <div class="text-center py-16">
            <i data-lucide="user-x" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
            <p class="text-sm text-slate-500">No members in this department yet.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium">Name</th>
                        <th class="text-left px-5 py-3 font-medium">Email</th>
                        <th class="text-left px-5 py-3 font-medium">Role</th>
                        <th class="text-left px-5 py-3 font-medium">Status</th>
                        <th class="text-left px-5 py-3 font-medium">Position</th>
                        <th class="text-left px-5 py-3 font-medium">Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($users->sortBy('name') as $u)
                        @php
                            $statusBadge = match($u->status) {
                                'active'   => 'bg-emerald-50 text-emerald-700',
                                'pending'  => 'bg-amber-50 text-amber-700',
                                'rejected' => 'bg-rose-50 text-rose-700',
                                default    => 'bg-slate-100 text-slate-600',
                            };
                            $roleBadge = match($u->role) {
                                'admin'    => 'bg-ua-red-50 text-ua-red-700',
                                'approver' => 'bg-indigo-50 text-indigo-700',
                                default    => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50 {{ $u->status === 'pending' ? 'bg-amber-50/30' : '' }}">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    @if($u->avatar)
                                        <img src="{{ $u->avatarUrl() }}" alt="{{ $u->name }}"
                                             class="w-8 h-8 rounded-full object-cover shrink-0">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-ua-red-400 to-ua-red-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="font-medium text-slate-900">{{ $u->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $u->email }}</td>
                            <td class="px-5 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $roleBadge }}">
                                    {{ ucfirst($u->role) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusBadge }}">
                                    {{ ucfirst($u->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $u->requested_position ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500 text-xs">{{ $u->created_at->format('M d, Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.users.show', $u) }}"
                                   class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">
                                    View →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
