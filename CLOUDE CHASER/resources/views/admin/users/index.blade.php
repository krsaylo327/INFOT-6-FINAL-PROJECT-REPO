@extends('layouts.app')

@section('title', 'User Management')
@section('eyebrow', 'Administration')
@section('page_title', 'User Management')

@section('content')

{{-- ── Stats Row ──────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
    @foreach([
        ['label'=>'Total Users',  'value'=>$stats['total'],     'color'=>'text-slate-800',   'bg'=>'bg-slate-50',    'dot'=>'bg-slate-400'],
        ['label'=>'Active',       'value'=>$stats['active'],    'color'=>'text-emerald-700', 'bg'=>'bg-emerald-50',  'dot'=>'bg-emerald-500'],
        ['label'=>'Pending',      'value'=>$stats['pending'],   'color'=>'text-amber-700',   'bg'=>'bg-amber-50',    'dot'=>'bg-amber-500'],
        ['label'=>'Disabled',     'value'=>$stats['disabled'],  'color'=>'text-orange-700',  'bg'=>'bg-orange-50',   'dot'=>'bg-orange-500'],
        ['label'=>'Rejected',     'value'=>$stats['rejected'],  'color'=>'text-rose-700',    'bg'=>'bg-rose-50',     'dot'=>'bg-rose-500'],
    ] as $s)
    <div class="bg-white rounded-2xl border border-slate-200 px-4 py-3 flex items-center gap-3">
        <span class="w-2.5 h-2.5 rounded-full {{ $s['dot'] }} shrink-0"></span>
        <div>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">{{ $s['label'] }}</p>
            <p class="text-xl font-bold {{ $s['color'] }}">{{ $s['value'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Filter / Search Bar ──────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-48">
        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
        <input type="text" id="searchInput" placeholder="Search name or email…"
               class="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-300">
    </div>

    <select id="filterStatus" class="text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ua-red-300 bg-white">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="pending">Pending</option>
        <option value="disabled">Disabled</option>
        <option value="rejected">Rejected</option>
    </select>

    <select id="filterRole" class="text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ua-red-300 bg-white">
        <option value="">All Roles</option>
        <option value="admin">Admin</option>
        <option value="approver">Approver</option>
        <option value="dean">Dean</option>
        <option value="traveler">Traveler</option>
    </select>

    <select id="filterDept" class="text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ua-red-300 bg-white">
        <option value="">All Departments</option>
        @foreach($departments as $dept)
            <option value="{{ $dept->id }}">{{ $dept->abbreviation ?? $dept->name }}</option>
        @endforeach
    </select>

    <span id="resultCount" class="text-xs text-slate-400 ml-auto shrink-0"></span>
</div>

{{-- ── Users Table ──────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="usersTable">
            <thead class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left px-5 py-3">User</th>
                    <th class="text-left px-5 py-3">Role</th>
                    <th class="text-left px-5 py-3">Department</th>
                    <th class="text-left px-5 py-3">Status</th>
                    <th class="text-left px-5 py-3">Joined</th>
                    <th class="text-right px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" id="usersBody">
                @forelse($users as $u)
                <tr class="hover:bg-slate-50 transition-colors user-row"
                    data-name="{{ strtolower($u->name) }}"
                    data-email="{{ strtolower($u->email) }}"
                    data-status="{{ $u->status }}"
                    data-role="{{ $u->role }}"
                    data-dept="{{ $u->department_id ?? '' }}">

                    {{-- Avatar + Name --}}
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            @if($u->avatar)
                                <img src="{{ $u->avatarUrl() }}" class="w-8 h-8 rounded-full object-cover shrink-0">
                            @else
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-ua-red-400 to-ua-red-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900 truncate">{{ $u->name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ $u->email }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- Role --}}
                    <td class="px-5 py-3">
                        @php
                            $roleColor = match($u->role) {
                                'admin'    => 'bg-ua-red-50 text-ua-red-700',
                                'approver' => 'bg-indigo-50 text-indigo-700',
                                'dean'     => 'bg-purple-50 text-purple-700',
                                default    => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $roleColor }}">
                            {{ ucfirst($u->role) }}
                            @if($u->role === 'approver' && $u->approver_level)
                                · L{{ $u->approver_level }}
                            @endif
                        </span>
                    </td>

                    {{-- Department --}}
                    <td class="px-5 py-3 text-xs">
                        @if($u->department)
                            <div class="flex items-center gap-1.5">
                                @if($u->department->abbreviation)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-200 text-slate-700">{{ $u->department->abbreviation }}</span>
                                @endif
                                <span class="text-slate-600 truncate max-w-[180px]" title="{{ $u->department->name }}">{{ $u->department->name }}</span>
                            </div>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $u->statusBadgeClass() }}">
                            @if($u->isPending())   <i data-lucide="clock"          class="w-3 h-3"></i>
                            @elseif($u->isActive())    <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                            @elseif($u->isDisabled())  <i data-lucide="ban"            class="w-3 h-3"></i>
                            @elseif($u->isRejected())  <i data-lucide="x-circle"       class="w-3 h-3"></i>
                            @endif
                            {{ ucfirst($u->status) }}
                        </span>
                        @if($u->isDisabled() && $u->disabled_at)
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $u->disabled_at->diffForHumans() }}</p>
                        @endif
                    </td>

                    {{-- Joined --}}
                    <td class="px-5 py-3 text-xs text-slate-400">{{ $u->created_at->format('M d, Y') }}</td>

                    {{-- Actions --}}
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if($u->isPending())
                                <span class="text-[10px] font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Needs review</span>
                            @endif
                            <a href="{{ route('admin.users.show', $u) }}"
                               class="flex items-center gap-1 text-xs font-medium text-slate-600 border border-slate-200 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition-colors">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                View
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-slate-400 text-sm">No users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="emptyState" class="hidden px-5 py-12 text-center">
        <i data-lucide="users" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
        <p class="text-sm text-slate-400">No users match your filters.</p>
    </div>
</div>

<script>
(function () {
    const search  = document.getElementById('searchInput');
    const fStatus = document.getElementById('filterStatus');
    const fRole   = document.getElementById('filterRole');
    const fDept   = document.getElementById('filterDept');
    const count   = document.getElementById('resultCount');
    const empty   = document.getElementById('emptyState');
    const rows    = () => document.querySelectorAll('.user-row');

    function applyFilters() {
        const q      = search.value.toLowerCase().trim();
        const status = fStatus.value;
        const role   = fRole.value;
        const dept   = fDept.value;
        let visible  = 0;

        rows().forEach(row => {
            const match =
                (!q      || row.dataset.name.includes(q) || row.dataset.email.includes(q)) &&
                (!status || row.dataset.status === status) &&
                (!role   || row.dataset.role === role) &&
                (!dept   || row.dataset.dept === dept);

            row.classList.toggle('hidden', !match);
            if (match) visible++;
        });

        count.textContent = visible + ' user' + (visible !== 1 ? 's' : '');
        empty.classList.toggle('hidden', visible > 0);
    }

    [search, fStatus, fRole, fDept].forEach(el => el.addEventListener('input', applyFilters));
    applyFilters();
})();
</script>

@endsection
