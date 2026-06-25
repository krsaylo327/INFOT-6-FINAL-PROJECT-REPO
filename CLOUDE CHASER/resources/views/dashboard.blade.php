@extends('layouts.app')

@section('title', 'Dashboard')
@section('eyebrow', 'Welcome back')
@section('page_title', 'Dashboard')

@php
    $firstName = explode(' ', $user->name)[0] ?? $user->name;
    $greeting = now()->hour < 12 ? 'Good morning' : (now()->hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

@section('content')
    {{-- ======================= Welcome banner ======================= --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-ua-red-600 to-ua-red-800 p-6 sm:p-8 mb-6 text-white shadow-sm">
        <div class="absolute -right-10 -top-10 w-64 h-64 bg-white/10 rounded-full"></div>
        <div class="absolute -right-20 bottom-0 w-40 h-40 bg-white/5 rounded-full"></div>

        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-white/80">{{ $greeting }},</p>
                <h2 class="text-2xl sm:text-3xl font-bold">{{ $firstName }} 👋</h2>
                <p class="text-sm text-white/80 mt-1">
                    @if($role !== 'admin')
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="briefcase" class="w-3.5 h-3.5"></i>
                        {{ $user->department?->name ?? 'No department assigned' }}
                    </span>
                    <span class="mx-2 text-white/50">•</span>
                    @endif
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                        @if($user->role === 'dean' && $user->department?->abbreviation === 'PRES')
                            {{ $user->requested_position ?? 'University President' }}
                        @elseif($user->role === 'records_officer')
                            Records Officer
                        @else
                            {{ ucfirst($user->role) }}
                        @endif
                    </span>
                </p>
            </div>

            @if($role === 'traveler')
                <a href="{{ route('travel-requests.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-ua-red-700 rounded-xl font-semibold shadow-sm hover:bg-ua-red-50">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    New Travel Request
                </a>
            @elseif($role === 'approver')
                <a href="{{ route('approvals.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-ua-red-700 rounded-xl font-semibold shadow-sm hover:bg-ua-red-50">
                    <i data-lucide="inbox" class="w-4 h-4"></i>
                    Review Approvals
                </a>
            @elseif($role === 'admin')
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-ua-red-700 rounded-xl font-semibold shadow-sm hover:bg-ua-red-50">
                    <i data-lucide="users" class="w-4 h-4"></i>
                    Manage Users
                </a>
            @elseif($role === 'records_officer')
                <a href="{{ route('records-office.outgoing') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-ua-red-700 rounded-xl font-semibold shadow-sm hover:bg-ua-red-50">
                    <i data-lucide="stamp" class="w-4 h-4"></i>
                    Process Travel Orders
                </a>
            @elseif($role === 'dean')
                @if($user->department?->abbreviation === 'PRES')
                    {{-- President — reviews the inbox and forwards invitations to deans --}}
                    <a href="{{ route('received-invitations.index') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-ua-red-700 rounded-xl font-semibold shadow-sm hover:bg-ua-red-50">
                        <i data-lucide="inbox" class="w-4 h-4"></i>
                        Open Inbox
                    </a>
                @else
                    <a href="{{ route('assignments.create') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-ua-red-700 rounded-xl font-semibold shadow-sm hover:bg-ua-red-50">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Assign Travel
                    </a>
                @endif
            @endif
        </div>
    </div>

    {{-- ======================= TRAVELER DASHBOARD ======================= --}}
    @if($role === 'traveler')
        {{-- Auto-generated Travel Orders awaiting next step --}}
        @if(!empty($upcomingTravelOrders) && $upcomingTravelOrders->isNotEmpty())
            <div class="mb-6 bg-indigo-50 border-2 border-indigo-200 rounded-2xl p-5 sm:p-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white">
                        <i data-lucide="file-check" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-indigo-900">Travel Orders In Progress</h3>
                        <p class="text-xs text-indigo-700">Auto-generated from your approved endorsements — waiting on the next office.</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-600 text-white font-semibold">{{ $upcomingTravelOrders->count() }}</span>
                </div>

                <div class="space-y-3">
                    @foreach($upcomingTravelOrders as $to)
                        @php
                            $statusLabel = match($to->status) {
                                'pending_signature' => 'Awaiting President\'s Signature',
                                'pending_release'   => 'Awaiting Records Release',
                                default             => ucfirst(str_replace('_', ' ', $to->status)),
                            };
                            $statusColor = $to->status === 'pending_signature' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700';
                        @endphp
                        <div class="bg-white rounded-xl border border-indigo-100 p-4 flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusColor }}">
                                        <i data-lucide="clock" class="w-3 h-3"></i> {{ $statusLabel }}
                                    </span>
                                    <span class="text-xs font-mono font-semibold text-ua-red-700">{{ $to->to_number }}</span>
                                </div>
                                <p class="text-sm font-semibold text-slate-900 truncate">{{ $to->event_name }}</p>
                                <p class="text-xs text-slate-600 mt-0.5">
                                    <i data-lucide="calendar" class="inline w-3 h-3"></i>
                                    {{ $to->date_from->format('M j') }} – {{ $to->date_to->format('M j, Y') }}
                                    <span class="mx-1 text-slate-300">|</span>
                                    <i data-lucide="map-pin" class="inline w-3 h-3"></i>
                                    {{ $to->destination }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('travel-orders.show', $to) }}"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    View TO
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Pending assignments awaiting acknowledgement --}}
        @if(!empty($pendingAssignments) && $pendingAssignments->isNotEmpty())
            <div class="mb-6 bg-indigo-50 border border-indigo-200 rounded-2xl p-5 sm:p-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-indigo-900">Trips Assigned to You</h3>
                        <p class="text-xs text-indigo-700">Please acknowledge or decline each assignment below.</p>
                    </div>
                    <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-indigo-600 text-white font-semibold">{{ $pendingAssignments->count() }}</span>
                </div>

                <div class="space-y-3">
                    @foreach($pendingAssignments as $assigned)
                        <div class="bg-white rounded-xl border border-indigo-100 p-4 flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-slate-500">{{ $assigned->request_no }}</span>
                                    <span class="text-xs text-slate-400">•</span>
                                    <span class="text-xs text-slate-500">
                                        assigned by <span class="font-semibold text-slate-700">{{ $assigned->assigner->name ?? 'Someone' }}</span>
                                    </span>
                                </div>
                                <p class="text-sm font-semibold text-slate-900 truncate">{{ $assigned->destination }}</p>
                                <p class="text-xs text-slate-600 mt-0.5">
                                    <i data-lucide="calendar" class="inline w-3 h-3"></i>
                                    {{ $assigned->date_from->format('M d') }} – {{ $assigned->date_to->format('M d, Y') }}
                                    <span class="mx-1 text-slate-300">|</span>
                                    <i data-lucide="circle-dollar-sign" class="inline w-3 h-3"></i>
                                    ₱{{ number_format((float)$assigned->estimated_cost, 2) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('travel-requests.show', $assigned) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Details
                                </a>
                                <form method="POST" action="{{ route('assignments.acknowledge', $assigned) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        Acknowledge
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('assignments.decline', $assigned) }}" class="inline"
                                      onsubmit="return confirm('Decline this travel assignment?');">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 rounded-lg">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                        Decline
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @include('partials.stat-card', ['label' => 'Total Requests', 'value' => $totalRequests, 'icon' => 'file-text', 'color' => 'slate'])
            @include('partials.stat-card', ['label' => 'Pending', 'value' => $pendingRequests, 'icon' => 'clock', 'color' => 'amber'])
            @include('partials.stat-card', ['label' => 'Approved', 'value' => $approvedRequests, 'icon' => 'check-circle-2', 'color' => 'emerald'])
            @include('partials.stat-card', ['label' => 'Rejected', 'value' => $rejectedRequests, 'icon' => 'x-circle', 'color' => 'rose'])
        </div>

        {{-- Upcoming trips --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="calendar-check" class="w-5 h-5 text-ua-red-600"></i>
                <h3 class="font-semibold">Upcoming Trips</h3>
            </div>

            @if($upcomingTrips->isEmpty())
                <div class="text-center py-8">
                    <i data-lucide="map" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                    <p class="text-sm text-slate-500">No upcoming approved trips yet.</p>
                </div>
            @else
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($upcomingTrips as $trip)
                        <a href="{{ route('travel-requests.show', $trip) }}"
                           class="block p-4 rounded-xl border border-slate-200 hover:border-ua-red-300 hover:shadow-sm">
                            <div class="flex items-start justify-between mb-2">
                                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                                    <i data-lucide="plane" class="w-5 h-5 text-emerald-600"></i>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-medium">Approved</span>
                            </div>
                            <p class="font-semibold text-sm truncate">{{ $trip->destination }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $trip->date_from->format('M d') }} – {{ $trip->date_to->format('M d, Y') }}
                            </p>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Recent requests --}}
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-5 h-5 text-slate-600"></i>
                    <h3 class="font-semibold">Recent Requests</h3>
                </div>
                <a href="{{ route('travel-requests.index') }}" class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">
                    View all →
                </a>
            </div>

            @if($recentRequests->isEmpty())
                <div class="text-center py-10">
                    <i data-lucide="inbox" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                    <p class="text-sm text-slate-500 mb-3">No travel requests yet.</p>
                    <a href="{{ route('travel-requests.create') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ua-red-600 hover:text-ua-red-700">
                        <i data-lucide="plus" class="w-4 h-4"></i> Create your first request
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
                            <tr>
                                <th class="text-left px-5 py-3 font-medium">Request No</th>
                                <th class="text-left px-5 py-3 font-medium">Destination</th>
                                <th class="text-left px-5 py-3 font-medium">Dates</th>
                                <th class="text-left px-5 py-3 font-medium">Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recentRequests as $request)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3 font-mono text-xs">{{ $request->request_no }}</td>
                                    <td class="px-5 py-3 font-medium">{{ $request->destination }}</td>
                                    <td class="px-5 py-3 text-slate-600">
                                        {{ $request->date_from->format('M d') }} – {{ $request->date_to->format('M d, Y') }}
                                    </td>
                                    <td class="px-5 py-3">
                                        @include('partials.status-pill', ['status' => $request->status])
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('travel-requests.show', $request) }}" class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">
                                            View →
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    {{-- ======================= APPROVER DASHBOARD ======================= --}}
    @if($role === 'approver')
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @include('partials.stat-card', ['label' => 'Pending My Approval', 'value' => $pendingApprovals, 'icon' => 'inbox', 'color' => 'ua-red', 'highlight' => true])
            @include('partials.stat-card', ['label' => 'Approved (this month)', 'value' => $approvedThisMonth, 'icon' => 'check-circle-2', 'color' => 'emerald'])
            @include('partials.stat-card', ['label' => 'Rejected (this month)', 'value' => $rejectedThisMonth, 'icon' => 'x-circle', 'color' => 'rose'])
            @include('partials.stat-card', ['label' => 'Dept. Requests', 'value' => $departmentRequests, 'icon' => 'building-2', 'color' => 'slate'])
        </div>

        {{-- Awaiting My Approval --}}
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
            <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="zap" class="w-5 h-5 text-ua-red-600"></i>
                    <h3 class="font-semibold">Awaiting Your Approval</h3>
                    @if($pendingApprovals > 0)
                        <span class="ml-1 text-[10px] px-2 py-0.5 rounded-full bg-ua-red-50 text-ua-red-700 font-semibold">{{ $pendingApprovals }}</span>
                    @endif
                </div>
                <a href="{{ route('approvals.index') }}" class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">
                    View all →
                </a>
            </div>

            @if($awaitingApproval->isEmpty())
                <div class="text-center py-10">
                    <i data-lucide="check-check" class="w-8 h-8 text-emerald-300 mx-auto mb-2"></i>
                    <p class="text-sm text-slate-500">You're all caught up! 🎉</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
                            <tr>
                                <th class="text-left px-5 py-3 font-medium">Request</th>
                                <th class="text-left px-5 py-3 font-medium">Traveler</th>
                                <th class="text-left px-5 py-3 font-medium">Destination</th>
                                <th class="text-left px-5 py-3 font-medium">Dates</th>
                                <th class="text-left px-5 py-3 font-medium">Level</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($awaitingApproval as $approval)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3 font-mono text-xs">{{ $approval->travelRequest->request_no }}</td>
                                    <td class="px-5 py-3 font-medium">{{ $approval->travelRequest->user->name }}</td>
                                    <td class="px-5 py-3">{{ $approval->travelRequest->destination }}</td>
                                    <td class="px-5 py-3 text-slate-600">
                                        {{ $approval->travelRequest->date_from->format('M d') }} – {{ $approval->travelRequest->date_to->format('M d, Y') }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-medium">
                                            Level {{ $approval->level }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('travel-requests.show', $approval->travelRequest) }}" class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">
                                            Review →
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Recent decisions --}}
        <section class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="history" class="w-5 h-5 text-slate-600"></i>
                <h3 class="font-semibold">Your Recent Decisions</h3>
            </div>

            @if($recentDecisions->isEmpty())
                <p class="text-sm text-slate-500">No decisions yet.</p>
            @else
                <ul class="space-y-3">
                    @foreach($recentDecisions as $decision)
                        <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0
                                        {{ $decision->action === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                <i data-lucide="{{ $decision->action === 'approved' ? 'check' : 'x' }}" class="w-4 h-4"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm">
                                    You <span class="font-semibold">{{ $decision->action }}</span>
                                    a request from
                                    <span class="font-semibold">{{ $decision->travelRequest->user->name }}</span>
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $decision->travelRequest->destination }} •
                                    {{ $decision->acted_at->diffForHumans() }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    {{-- ======================= DEAN DASHBOARD ======================= --}}
    @if($role === 'dean')
        {{-- Stat cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @include('partials.stat-card', [
                'label'     => $isPres ? 'All Staff' : 'My Department Staff',
                'value'     => $totalStaff,
                'icon'      => 'users',
                'color'     => 'slate',
            ])
            @include('partials.stat-card', [
                'label'     => 'Trips Assigned',
                'value'     => $totalAssigned,
                'icon'      => 'send',
                'color'     => 'indigo',
                'href'      => route('assignments.index'),
            ])
            @include('partials.stat-card', [
                'label'     => 'Awaiting Acknowledgment',
                'value'     => $pendingAck,
                'icon'      => 'clock',
                'color'     => 'amber',
                'highlight' => $pendingAck > 0,
            ])
        </div>

        {{-- Endorsement Analytics (college dean only) --}}
        @if(!$isPres)
        <section class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 mb-6">
            <div class="flex items-center gap-2 mb-5">
                <i data-lucide="bar-chart-3" class="w-5 h-5 text-ua-red-600"></i>
                <h3 class="font-semibold">Endorsement Analytics</h3>
                <span class="text-xs text-slate-400">Overview of staff you've endorsed</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-2xl font-bold text-slate-800">{{ $endorsementsTotal }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Total Endorsements</p>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                    <p class="text-2xl font-bold text-amber-700">{{ $endorsementsPending }}</p>
                    <p class="text-xs text-amber-600 mt-0.5">Under VP Review</p>
                </div>
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                    <p class="text-2xl font-bold text-emerald-700">{{ $endorsementsApproved }}</p>
                    <p class="text-xs text-emerald-600 mt-0.5">Approved</p>
                </div>
                <div class="rounded-xl border border-rose-100 bg-rose-50 p-4">
                    <p class="text-2xl font-bold text-rose-700">{{ $endorsementsRejected }}</p>
                    <p class="text-xs text-rose-600 mt-0.5">Returned</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 pt-4 border-t border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                        <i data-lucide="plane-takeoff" class="w-4 h-4 text-indigo-600"></i>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-slate-800 leading-none">{{ $staffOnTravel }}</p>
                        <p class="text-xs text-slate-500 mt-1">Currently on Travel</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-sky-50 flex items-center justify-center shrink-0">
                        <i data-lucide="file-clock" class="w-4 h-4 text-sky-600"></i>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-slate-800 leading-none">{{ $tosActive }}</p>
                        <p class="text-xs text-slate-500 mt-1">Active Travel Orders</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center shrink-0">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-teal-600"></i>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-slate-800 leading-none">{{ $tosCompleted }}</p>
                        <p class="text-xs text-slate-500 mt-1">Completed Trips</p>
                    </div>
                </div>
            </div>
        </section>
        @endif

        {{-- My Staff --}}
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
            <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="w-5 h-5 text-slate-600"></i>
                    <h3 class="font-semibold">{{ $isPres ? 'All University Staff' : 'Staff Under My Department' }}</h3>
                    <span class="text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ $totalStaff }}</span>
                </div>
                @if($isPres)
                    <a href="{{ route('received-invitations.index') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-lg text-xs font-semibold">
                        <i data-lucide="inbox" class="w-3.5 h-3.5"></i>
                        Open Inbox
                    </a>
                @else
                    <a href="{{ route('assignments.create') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-lg text-xs font-semibold">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Assign Travel
                    </a>
                @endif
            </div>

            @if($staff->isEmpty())
                <div class="text-center py-12">
                    <i data-lucide="user-x" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                    <p class="text-sm text-slate-500">No active staff found{{ $isPres ? '' : ' in your department' }}.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
                            <tr>
                                <th class="text-left px-5 py-3 font-medium">Name</th>
                                @if($isPres)
                                    <th class="text-left px-5 py-3 font-medium">Department</th>
                                @endif
                                <th class="text-left px-5 py-3 font-medium">Position</th>
                                <th class="text-left px-5 py-3 font-medium">Email</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($staff as $member)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2.5">
                                            @if($member->avatar)
                                                <img src="{{ $member->avatarUrl() }}" class="w-8 h-8 rounded-full object-cover shrink-0">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-ua-red-400 to-ua-red-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                                </div>
                                            @endif
                                            <span class="font-medium text-slate-900">{{ $member->name }}</span>
                                        </div>
                                    </td>
                                    @if($isPres)
                                        <td class="px-5 py-3 text-slate-600">
                                            @if($member->department)
                                                <span class="inline-flex items-center gap-1">
                                                    <span class="text-xs font-mono text-slate-400">{{ $member->department->abbreviation }}</span>
                                                    {{ $member->department->name }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-5 py-3 text-slate-500">{{ $member->requested_position ?? '—' }}</td>
                                    <td class="px-5 py-3 text-slate-500">{{ $member->email }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('assignments.create') }}?user_id={{ $member->id }}"
                                           class="inline-flex items-center gap-1 text-xs font-medium text-ua-red-600 hover:text-ua-red-700">
                                            <i data-lucide="send" class="w-3 h-3"></i>
                                            Assign →
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Recent assignments I made --}}
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-5 h-5 text-slate-600"></i>
                    <h3 class="font-semibold">My Recent Assignments</h3>
                </div>
                <a href="{{ route('assignments.index') }}" class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">
                    View all →
                </a>
            </div>

            @if($recentAssignments->isEmpty())
                <div class="text-center py-10">
                    <i data-lucide="send" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                    <p class="text-sm text-slate-500">No assignments made yet.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
                            <tr>
                                <th class="text-left px-5 py-3 font-medium">Staff Member</th>
                                <th class="text-left px-5 py-3 font-medium">Destination</th>
                                <th class="text-left px-5 py-3 font-medium">Dates</th>
                                <th class="text-left px-5 py-3 font-medium">Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recentAssignments as $a)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3 font-medium">{{ $a->user->name }}</td>
                                    <td class="px-5 py-3">{{ $a->destination }}</td>
                                    <td class="px-5 py-3 text-slate-500">
                                        {{ $a->date_from->format('M d') }} – {{ $a->date_to->format('M d, Y') }}
                                    </td>
                                    <td class="px-5 py-3">
                                        @include('partials.status-pill', ['status' => $a->status])
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('travel-requests.show', $a) }}" class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">
                                            View →
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    {{-- ======================= RECORDS OFFICER DASHBOARD ======================= --}}
    @if($role === 'records_officer')
        {{-- Alert: TOs waiting for release --}}
        @if($pendingRelease > 0)
        <div class="mb-6 bg-amber-50 border border-amber-200 rounded-2xl p-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500 flex items-center justify-center text-white shrink-0">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-amber-900">{{ $pendingRelease }} Travel Order{{ $pendingRelease !== 1 ? 's' : '' }} Awaiting Release</p>
                    <p class="text-xs text-amber-700 mt-0.5">President has approved these — assign a TO number and stamp them as released.</p>
                </div>
                <a href="{{ route('records-office.outgoing') }}"
                   class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-semibold">
                    <i data-lucide="stamp" class="w-4 h-4"></i> Process Now
                </a>
            </div>
        </div>
        @endif

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @include('partials.stat-card', [
                'label' => 'Pending Release',
                'value' => $pendingRelease,
                'icon'  => 'file-output',
                'color' => $pendingRelease > 0 ? 'amber' : 'emerald',
                'highlight' => $pendingRelease > 0,
                'href'  => route('records-office.outgoing'),
            ])
            @include('partials.stat-card', [
                'label' => 'Invitations Not Forwarded',
                'value' => $pendingIncoming,
                'icon'  => 'inbox',
                'color' => $pendingIncoming > 0 ? 'amber' : 'slate',
                'highlight' => $pendingIncoming > 0,
                'href'  => route('records-office.incoming'),
            ])
            @include('partials.stat-card', [
                'label' => 'Released This Month',
                'value' => $releasedThisMonth,
                'icon'  => 'calendar-check',
                'color' => 'indigo',
            ])
            @include('partials.stat-card', [
                'label' => 'Invitations Logged by Me',
                'value' => $totalLoggedByMe,
                'icon'  => 'book-open',
                'color' => 'slate',
            ])
        </div>

        {{-- Pending Release Queue --}}
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
            <div class="flex items-center justify-between p-5 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="file-output" class="w-5 h-5 text-ua-red-600"></i>
                    <h3 class="font-semibold">Travel Orders Pending Release</h3>
                    @if($pendingRelease > 0)
                        <span class="ml-1 text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-semibold">{{ $pendingRelease }}</span>
                    @endif
                </div>
                <a href="{{ route('records-office.outgoing') }}" class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">View all →</a>
            </div>

            @if($pendingQueue->isEmpty())
                <div class="text-center py-10">
                    <i data-lucide="check-check" class="w-8 h-8 text-emerald-300 mx-auto mb-2"></i>
                    <p class="text-sm text-slate-500">No Travel Orders waiting — you're all caught up!</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
                            <tr>
                                <th class="text-left px-5 py-3 font-medium">Traveler</th>
                                <th class="text-left px-5 py-3 font-medium">Department</th>
                                <th class="text-left px-5 py-3 font-medium">Event</th>
                                <th class="text-left px-5 py-3 font-medium">Approved By President</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($pendingQueue as $to)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $to->traveler?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $to->department?->abbreviation ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-700 max-w-[220px] truncate">{{ $to->event_name }}</td>
                                <td class="px-5 py-3 text-slate-500 text-xs">{{ $to->issued_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('records-office.outgoing') }}"
                                       class="inline-flex items-center gap-1 text-xs font-semibold text-ua-red-600 hover:text-ua-red-700">
                                        <i data-lucide="stamp" class="w-3.5 h-3.5"></i> Release →
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Two-column: recent releases + recent incoming logs --}}
        <div class="grid lg:grid-cols-2 gap-6">
            <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between p-5 border-b border-slate-200">
                    <div class="flex items-center gap-2">
                        <i data-lucide="stamp" class="w-5 h-5 text-emerald-600"></i>
                        <h3 class="font-semibold">My Recent Releases</h3>
                    </div>
                    <a href="{{ route('records-office.outgoing') }}" class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">View all →</a>
                </div>
                @if($recentReleased->isEmpty())
                    <div class="text-center py-8">
                        <i data-lucide="file-output" class="w-7 h-7 text-slate-300 mx-auto mb-2"></i>
                        <p class="text-sm text-slate-400">No releases yet.</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-50">
                        @foreach($recentReleased as $to)
                        <li class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                                <i data-lucide="stamp" class="w-4 h-4 text-emerald-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-xs font-mono font-bold text-ua-red-700">{{ $to->to_number }}</p>
                                    <span class="text-slate-300 text-xs">·</span>
                                    <p class="text-xs text-slate-500">{{ $to->department?->abbreviation ?? '—' }}</p>
                                </div>
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $to->traveler?->name ?? '—' }}</p>
                            </div>
                            <p class="text-[11px] text-slate-400 shrink-0">{{ $to->records_released_at?->format('M j') }}</p>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between p-5 border-b border-slate-200">
                    <div class="flex items-center gap-2">
                        <i data-lucide="inbox" class="w-5 h-5 text-slate-600"></i>
                        <h3 class="font-semibold">My Recent Incoming Logs</h3>
                    </div>
                    <a href="{{ route('records-office.incoming') }}" class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">View all →</a>
                </div>
                @if($recentIncoming->isEmpty())
                    <div class="text-center py-8">
                        <i data-lucide="inbox" class="w-7 h-7 text-slate-300 mx-auto mb-2"></i>
                        <p class="text-sm text-slate-400">No invitations logged yet.</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-50">
                        @foreach($recentIncoming as $inv)
                        <li class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0
                                {{ $inv->status === 'new' ? 'bg-amber-50' : ($inv->status === 'forwarded' ? 'bg-emerald-50' : 'bg-slate-50') }}">
                                <i data-lucide="{{ $inv->status === 'forwarded' ? 'send' : ($inv->status === 'declined' ? 'x' : 'clock') }}"
                                   class="w-4 h-4 {{ $inv->status === 'forwarded' ? 'text-emerald-600' : ($inv->status === 'declined' ? 'text-slate-400' : 'text-amber-500') }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $inv->event_name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $inv->sender_org }}</p>
                            </div>
                            <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $inv->statusBadgeClass() }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif

    {{-- ======================= ADMIN DASHBOARD ======================= --}}
    @if($role === 'admin')
        {{-- Stat cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @include('partials.stat-card', ['label' => 'Total Users',      'value' => $totalUsers,       'icon' => 'users',       'color' => 'slate',   'href' => route('admin.users.index')])
            @include('partials.stat-card', ['label' => 'Active Users',     'value' => $activeUsers,      'icon' => 'user-check',  'color' => 'emerald'])
            @include('partials.stat-card', ['label' => 'Pending Approval', 'value' => $pendingUsers,     'icon' => 'user-plus',   'color' => 'amber',   'highlight' => $pendingUsers > 0, 'href' => route('admin.users.index')])
            @include('partials.stat-card', ['label' => 'Departments',      'value' => $totalDepartments, 'icon' => 'building-2',  'color' => 'indigo'])
        </div>

        {{-- Pending registrations callout --}}
        @if($pendingUsers > 0)
            <div class="mb-6 bg-amber-50 border border-amber-200 rounded-2xl p-5 sm:p-6">
                <div class="flex items-center gap-2 mb-1">
                    <i data-lucide="user-plus" class="w-5 h-5 text-amber-600"></i>
                    <h3 class="font-semibold text-amber-900">{{ $pendingUsers }} pending registration{{ $pendingUsers > 1 ? 's' : '' }} awaiting approval</h3>
                </div>
                <p class="text-sm text-amber-700 mb-3">New users have registered and are waiting for your review.</p>
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-semibold shadow-sm">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    Review Pending Users
                </a>
            </div>
        @endif

        {{-- Recent registrations --}}
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
            <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="user-round-plus" class="w-5 h-5 text-slate-600"></i>
                    <h3 class="font-semibold">Recent Registrations</h3>
                </div>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">
                    View all →
                </a>
            </div>

            @if($recentRegistrations->isEmpty())
                <div class="text-center py-10">
                    <i data-lucide="users" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                    <p class="text-sm text-slate-500">No users yet.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
                            <tr>
                                <th class="text-left px-5 py-3 font-medium">Name</th>
                                <th class="text-left px-5 py-3 font-medium">Email</th>
                                <th class="text-left px-5 py-3 font-medium">Role</th>
                                <th class="text-left px-5 py-3 font-medium">Department</th>
                                <th class="text-left px-5 py-3 font-medium">Status</th>
                                <th class="text-left px-5 py-3 font-medium">Joined</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recentRegistrations as $u)
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
                                                     class="w-7 h-7 rounded-full object-cover shrink-0">
                                            @else
                                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-ua-red-400 to-ua-red-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
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
                                    <td class="px-5 py-3 text-slate-600">
                                        @if($u->department)
                                            <span class="inline-flex items-center gap-1">
                                                @if($u->department->abbreviation)
                                                    <span class="text-xs font-mono text-slate-400">{{ $u->department->abbreviation }}</span>
                                                @endif
                                                {{ $u->department->name }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusBadge }}">
                                            {{ ucfirst($u->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500 text-xs">{{ $u->created_at->format('M d, Y') }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('admin.users.show', $u) }}" class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">
                                            View →
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Charts --}}
        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-3">Analytics</p>
        <div class="grid lg:grid-cols-3 gap-4 mb-6">
            {{-- Users by department --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <i data-lucide="building-2" class="w-4 h-4 text-indigo-600"></i>
                    <h3 class="text-sm font-semibold">Users by Department</h3>
                </div>
                <canvas id="chartDept" height="160"></canvas>
            </div>
            {{-- Users by role --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <i data-lucide="pie-chart" class="w-4 h-4 text-ua-red-600"></i>
                    <h3 class="text-sm font-semibold">Users by Role</h3>
                </div>
                <canvas id="chartRole" height="160"></canvas>
            </div>
        </div>

        <script src="{{ asset('js/chart.min.js') }}"></script>
        <script>
        (function () {
            // Users by department (horizontal bar)
            new Chart(document.getElementById('chartDept'), {
                type: 'bar',
                data: {
                    labels: @json($chartDeptLabels),
                    datasets: [{
                        label: 'Users',
                        data: @json($chartDeptCounts),
                        backgroundColor: '#6366f1cc',
                        borderColor: '#6366f1',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                        y: { grid: { display: false }, ticks: { font: { size: 11 } } }
                    }
                }
            });

            // Users by role (doughnut)
            const roles = @json($chartRoles);
            const roleColors = { admin: '#c40000', approver: '#6366f1', traveler: '#10b981' };
            new Chart(document.getElementById('chartRole'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(roles).map(r => r.charAt(0).toUpperCase() + r.slice(1)),
                    datasets: [{
                        data: Object.values(roles),
                        backgroundColor: Object.keys(roles).map(r => roleColors[r] ?? '#94a3b8'),
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } },
                    cutout: '65%',
                }
            });
        })();
        </script>
    @endif

    {{-- ======================= RECENT ACTIVITY (all roles) ======================= --}}
    @if($recentActivity->isNotEmpty())
    <section class="mt-6 bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="flex items-center gap-2 p-5 border-b border-slate-200">
            <i data-lucide="activity" class="w-5 h-5 text-slate-600"></i>
            <h3 class="font-semibold">Recent Activity</h3>
        </div>
        <ul class="divide-y divide-slate-50">
            @foreach($recentActivity as $log)
            @php
                $icon = match(true) {
                    str_starts_with($log->action, 'approval.')    => 'shield-check',
                    str_starts_with($log->action, 'assignment.')  => 'user-plus',
                    str_starts_with($log->action, 'request.')     => 'file-text',
                    str_starts_with($log->action, 'user.')        => 'user',
                    str_starts_with($log->action, 'profile.')     => 'settings',
                    default                                        => 'clock',
                };
                $color = match(true) {
                    str_contains($log->action, 'approved') || str_contains($log->action, 'acknowledged') => 'text-emerald-600 bg-emerald-50',
                    str_contains($log->action, 'rejected') || str_contains($log->action, 'declined')     => 'text-rose-600 bg-rose-50',
                    str_contains($log->action, 'disabled') || str_contains($log->action, 'suspended')    => 'text-orange-600 bg-orange-50',
                    default                                                                               => 'text-slate-600 bg-slate-100',
                };
                $label = str_replace(['.', '_'], [' → ', ' '], $log->action);
            @endphp
            <li class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50">
                <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $color }}">
                    <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 capitalize">{{ $label }}</p>
                    @if($log->auditable instanceof \App\Models\TravelRequest)
                        <p class="text-xs text-slate-400 truncate">
                            {{ $log->auditable->request_no }} · {{ $log->auditable->destination }}
                        </p>
                    @endif
                </div>
                <span class="text-xs text-slate-400 shrink-0">{{ $log->created_at->diffForHumans() }}</span>
            </li>
            @endforeach
        </ul>
    </section>
    @endif
@endsection
