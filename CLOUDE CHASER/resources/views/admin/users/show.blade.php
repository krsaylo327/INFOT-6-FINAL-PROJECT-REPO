@extends('layouts.app')

@section('title', $user->name)
@section('eyebrow', 'User Management')
@section('page_title', 'User Profile')

@section('content')
@php
    $roleColor = match($user->role) {
        'admin'    => 'bg-ua-red-50 text-ua-red-700',
        'approver' => 'bg-indigo-50 text-indigo-700',
        'dean'     => 'bg-purple-50 text-purple-700',
        default    => 'bg-slate-100 text-slate-600',
    };
@endphp

<div class="mb-5">
    <a href="{{ route('admin.users.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to User Management
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- ════════ LEFT: Profile + Details ════════ --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Hero card --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <div class="flex items-start gap-5">
                @if($user->avatar)
                    <img src="{{ $user->avatarUrl() }}" class="w-16 h-16 rounded-2xl object-cover shrink-0">
                @else
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-ua-red-500 to-ua-red-700 flex items-center justify-center text-white font-bold text-2xl shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h2 class="text-xl font-bold text-slate-900">{{ $user->name }}</h2>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $user->statusBadgeClass() }}">
                            @if($user->isPending())     <i data-lucide="clock"          class="w-3 h-3"></i>
                            @elseif($user->isActive())      <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                            @elseif($user->isDisabled())    <i data-lucide="ban"            class="w-3 h-3"></i>
                            @elseif($user->isRejected())    <i data-lucide="x-circle"       class="w-3 h-3"></i>
                            @endif
                            {{ ucfirst($user->status) }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $roleColor }}">
                            {{ ucfirst($user->role) }}
                            @if($user->role === 'approver' && $user->approver_type)
                                · {{ match($user->approver_type) {
                                    'vp_academic'       => 'VP Academic',
                                    'vp_research'       => 'VP Research',
                                    'research_director' => 'Research Director',
                                    default             => ucfirst($user->approver_type),
                                } }}
                            @endif
                        </span>
                    </div>
                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                    <p class="text-xs text-slate-400 mt-1">
                        Registered {{ $user->created_at->format('F d, Y') }} · {{ $user->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>

            {{-- Activity strip --}}
            <div class="mt-5 pt-5 border-t border-slate-100 grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ $travelRequestCount }}</p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-wide mt-0.5">Travel Requests</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ $approvalCount }}</p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-wide mt-0.5">Approvals Made</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ $user->department?->abbreviation ?? '—' }}</p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-wide mt-0.5">Department</p>
                </div>
            </div>
        </div>

        {{-- Registration details --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Account Details</h3>
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-0.5">Full Name</dt>
                    <dd class="font-medium text-slate-900">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-0.5">Email</dt>
                    <dd class="font-medium text-slate-900">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-0.5">Employee / Faculty ID</dt>
                    <dd class="font-medium text-slate-900">{{ $user->employee_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-0.5">Contact Number</dt>
                    <dd class="font-medium text-slate-900">{{ $user->contact_number ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-0.5">Department</dt>
                    <dd class="font-medium text-slate-900">{{ $user->department?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-0.5">Position / Designation</dt>
                    <dd class="font-medium text-slate-900">{{ $user->requested_position ?? '—' }}</dd>
                </div>
                @if($user->approved_at)
                <div>
                    <dt class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-0.5">Approved</dt>
                    <dd class="text-slate-700">{{ $user->approved_at->format('M d, Y, g:i A') }}</dd>
                </div>
                @endif
                @if($user->isDisabled() && $user->disabled_at)
                <div class="sm:col-span-2">
                    <dt class="text-[10px] uppercase tracking-wider font-semibold text-orange-400 mb-0.5">Reason Disabled</dt>
                    <dd class="text-orange-700 bg-orange-50 rounded-xl px-3 py-2 text-sm">{{ $user->disable_reason }}</dd>
                </div>
                @endif
                @if($user->isRejected() && $user->rejection_reason)
                <div class="sm:col-span-2">
                    <dt class="text-[10px] uppercase tracking-wider font-semibold text-rose-400 mb-0.5">Rejection Reason</dt>
                    <dd class="text-rose-700 bg-rose-50 rounded-xl px-3 py-2 text-sm">{{ $user->rejection_reason }}</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- Edit Role / Department (active or disabled users) --}}
        @if($user->isActive() || $user->isDisabled())
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4 flex items-center gap-2">
                <i data-lucide="settings-2" class="w-3.5 h-3.5"></i>
                Edit Account Settings
            </h3>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Role</label>
                        <select name="role" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-ua-red-300 bg-white" id="roleSelect">
                            @foreach(['traveler','approver','dean','admin','records_officer'] as $r)
                                <option value="{{ $r }}" {{ $user->role === $r ? 'selected' : '' }}>{{ $r === 'records_officer' ? 'Records Officer' : ucfirst($r) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="approverLevelField" class="{{ $user->role === 'approver' ? '' : 'hidden' }}">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Approver Type</label>
                        <select name="approver_type" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-ua-red-300 bg-white">
                            <option value="">— Select type —</option>
                            <option value="vp_academic"         {{ $user->approver_type === 'vp_academic'         ? 'selected' : '' }}>VP for Academic Affairs</option>
                            <option value="research_director"   {{ $user->approver_type === 'research_director'   ? 'selected' : '' }}>Research Director (Noting)</option>
                            <option value="vp_research"         {{ $user->approver_type === 'vp_research'         ? 'selected' : '' }}>VP for Research, Extension &amp; Innovation</option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Determines which category of travel this approver handles.</p>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Department</label>
                        <select name="department_id" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-ua-red-300 bg-white">
                            <option value="">— None —</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $user->department_id == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }} ({{ $dept->abbreviation ?? '—' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Position / Designation</label>
                        <input type="text" name="requested_position"
                               value="{{ old('requested_position', $user->requested_position) }}"
                               placeholder="e.g. Dean, Faculty, Staff"
                               class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-ua-red-300">
                    </div>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit"
                            class="flex items-center gap-2 px-5 py-2.5 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-semibold rounded-xl">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
        @endif

    </div>

    {{-- ════════ RIGHT: Action Panel ════════ --}}
    <div class="space-y-4">

        {{-- ── PENDING: approve / reject ── --}}
        @if($user->isPending())

        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="key-round" class="w-4 h-4 text-amber-600"></i>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Confirmation Code</p>
            </div>
            <p class="font-mono text-3xl font-bold tracking-[0.3em] text-slate-900 mb-2">{{ $token }}</p>
            <p class="text-xs text-amber-700 leading-relaxed">Enter this code below to approve or reject this registration.</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i> Approve Account
            </h3>
            <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                @csrf
                <input type="text" name="token" value="{{ old('token') }}"
                       placeholder="{{ $token }}" autocomplete="off" spellcheck="false"
                       class="w-full mb-3 px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono tracking-widest uppercase focus:outline-none focus:ring-2 focus:ring-emerald-200 @error('token') border-rose-400 @enderror">
                @error('token')<p class="text-xs text-rose-600 mb-2">{{ $message }}</p>@enderror
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold">
                    <i data-lucide="check" class="w-4 h-4"></i> Approve Registration
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                <i data-lucide="x-circle" class="w-4 h-4 text-rose-600"></i> Reject Account
            </h3>
            <form method="POST" action="{{ route('admin.users.reject', $user) }}">
                @csrf
                <textarea name="rejection_reason" rows="2" placeholder="Reason for rejection (optional)..."
                          class="w-full mb-3 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-200">{{ old('rejection_reason') }}</textarea>
                <input type="text" name="token" value="{{ old('token') }}"
                       placeholder="{{ $token }}" autocomplete="off" spellcheck="false"
                       class="w-full mb-3 px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono tracking-widest uppercase focus:outline-none focus:ring-2 focus:ring-rose-200">
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold">
                    <i data-lucide="x" class="w-4 h-4"></i> Reject Registration
                </button>
            </form>
        </div>

        {{-- ── ACTIVE: suspend ── --}}
        @elseif($user->isActive())

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                <p class="text-sm font-semibold text-slate-800">Account is Active</p>
            </div>
            <p class="text-xs text-slate-400 mb-5">This user can log in and use the system normally.</p>

            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3 flex items-center gap-1.5">
                <i data-lucide="ban" class="w-3.5 h-3.5 text-orange-500"></i>
                Disable Account
            </h3>
            <form method="POST" action="{{ route('admin.users.disable', $user) }}">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">
                        Reason for disabling <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="disable_reason" rows="3" required minlength="5"
                              placeholder="Briefly explain why this account is being disabled..."
                              class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-orange-200 resize-none">{{ old('disable_reason') }}</textarea>
                    @error('disable_reason')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                        onclick="return confirm('Disable {{ addslashes($user->name) }}? They will be logged out and unable to log in.')"
                        class="w-full flex items-center justify-center gap-2 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl text-sm font-semibold">
                    <i data-lucide="ban" class="w-4 h-4"></i>
                    Disable Account
                </button>
            </form>
        </div>

        {{-- ── DISABLED: enable ── --}}
        @elseif($user->isDisabled())

        <div class="bg-orange-50 border border-orange-200 rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-1">
                <i data-lucide="ban" class="w-4 h-4 text-orange-600"></i>
                <p class="text-sm font-semibold text-orange-800">Account Disabled</p>
            </div>
            <p class="text-xs text-orange-700 mb-1">
                Disabled {{ $user->disabled_at?->format('M d, Y') ?? 'recently' }}
            </p>
            @if($user->disable_reason)
                <p class="text-xs text-orange-700 italic mb-4">"{{ $user->disable_reason }}"</p>
            @endif

            <form method="POST" action="{{ route('admin.users.enable', $user) }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    Enable Account
                </button>
            </form>
        </div>

        {{-- ── REJECTED: reactivate ── --}}
        @elseif($user->isRejected())

        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-1">
                <i data-lucide="x-circle" class="w-4 h-4 text-rose-600"></i>
                <p class="text-sm font-semibold text-rose-800">Account Rejected</p>
            </div>
            @if($user->rejection_reason)
                <p class="text-xs text-rose-700 italic mb-4">"{{ $user->rejection_reason }}"</p>
            @else
                <p class="text-xs text-rose-600 mb-4">No reason was provided.</p>
            @endif

            <form method="POST" action="{{ route('admin.users.reactivate', $user) }}"
                  onsubmit="return confirm('Reactivate this account? The user will be able to log in immediately.')">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    Reactivate Account
                </button>
            </form>
        </div>

        @endif

    </div>
</div>

<script>
document.getElementById('roleSelect')?.addEventListener('change', function () {
    document.getElementById('approverLevelField').classList.toggle('hidden', this.value !== 'approver');
});
</script>

@endsection
