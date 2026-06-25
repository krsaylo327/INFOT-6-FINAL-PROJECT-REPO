@extends('layouts.app')

@section('title', 'Records Office')
@section('eyebrow', 'Records Office')
@section('page_title', 'Dashboard')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">

    {{-- Welcome banner --}}
    <div class="bg-gradient-to-r from-ua-red-700 to-ua-red-600 rounded-2xl p-6 text-white shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-ua-red-200 text-sm font-medium mb-0.5">Welcome back,</p>
                <h2 class="text-xl font-bold">{{ $user->name }}</h2>
                <p class="text-ua-red-200 text-sm mt-1">{{ now()->format('l, F j, Y') }}</p>
            </div>
            <div class="shrink-0 w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center">
                <i data-lucide="stamp" class="w-7 h-7 text-white"></i>
            </div>
        </div>

        @if($pendingRelease > 0)
        <div class="mt-4 flex items-center gap-2 bg-white/15 rounded-xl px-4 py-2.5 w-fit">
            <i data-lucide="alert-circle" class="w-4 h-4 text-amber-200 shrink-0"></i>
            <p class="text-sm text-white font-medium">
                {{ $pendingRelease }} Travel Order{{ $pendingRelease !== 1 ? 's' : '' }} waiting for your release
            </p>
            <a href="{{ route('records-office.outgoing') }}"
               class="ml-2 text-xs bg-white text-ua-red-700 font-semibold px-3 py-1 rounded-lg hover:bg-ua-red-50 transition-colors">
                Process now
            </a>
        </div>
        @endif
    </div>

    {{-- 4-up stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('records-office.outgoing') }}"
           class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:border-ua-red-300 hover:shadow transition-all">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3
                        {{ $pendingRelease > 0 ? 'bg-amber-100' : 'bg-emerald-50' }}">
                <i data-lucide="file-output" class="w-5 h-5 {{ $pendingRelease > 0 ? 'text-amber-600' : 'text-emerald-500' }}"></i>
            </div>
            <p class="text-2xl font-bold {{ $pendingRelease > 0 ? 'text-amber-600' : 'text-slate-700' }}">{{ $pendingRelease }}</p>
            <p class="text-xs text-slate-500 mt-0.5 leading-snug">Pending Release</p>
        </a>

        <a href="{{ route('records-office.incoming') }}"
           class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:border-ua-red-300 hover:shadow transition-all">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3
                        {{ $pendingIncoming > 0 ? 'bg-amber-100' : 'bg-slate-100' }}">
                <i data-lucide="inbox" class="w-5 h-5 {{ $pendingIncoming > 0 ? 'text-amber-600' : 'text-slate-500' }}"></i>
            </div>
            <p class="text-2xl font-bold {{ $pendingIncoming > 0 ? 'text-amber-600' : 'text-slate-700' }}">{{ $pendingIncoming }}</p>
            <p class="text-xs text-slate-500 mt-0.5 leading-snug">Invitations Not Yet Forwarded</p>
        </a>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center mb-3">
                <i data-lucide="calendar-check" class="w-5 h-5 text-indigo-500"></i>
            </div>
            <p class="text-2xl font-bold text-slate-700">{{ $releasedThisMonth }}</p>
            <p class="text-xs text-slate-500 mt-0.5 leading-snug">TOs Released This Month</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mb-3">
                <i data-lucide="book-open" class="w-5 h-5 text-slate-500"></i>
            </div>
            <p class="text-2xl font-bold text-slate-700">{{ $totalLoggedByMe }}</p>
            <p class="text-xs text-slate-500 mt-0.5 leading-snug">Invitations Logged by You</p>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('records-office.outgoing') }}"
           class="flex items-center gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:border-ua-red-300 hover:shadow transition-all group">
            <div class="w-12 h-12 rounded-xl bg-ua-red-50 flex items-center justify-center shrink-0 group-hover:bg-ua-red-100 transition-colors">
                <i data-lucide="file-output" class="w-6 h-6 text-ua-red-600"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-800">Outgoing Documents Register</p>
                <p class="text-xs text-slate-500 mt-0.5">Release Travel Orders and assign official TO numbers</p>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-slate-400 ml-auto shrink-0 group-hover:text-ua-red-500 transition-colors"></i>
        </a>

        <a href="{{ route('records-office.incoming') }}"
           class="flex items-center gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:border-ua-red-300 hover:shadow transition-all group">
            <div class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center shrink-0 group-hover:bg-slate-100 transition-colors">
                <i data-lucide="file-input" class="w-6 h-6 text-slate-500"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-800">Incoming Documents Register</p>
                <p class="text-xs text-slate-500 mt-0.5">Log incoming invitations and route them to the President</p>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-slate-400 ml-auto shrink-0 group-hover:text-slate-600 transition-colors"></i>
        </a>
    </div>

    {{-- Two-column activity feeds --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Recent TO Releases --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-700">Recent TO Releases</h2>
                <a href="{{ route('records-office.outgoing') }}" class="text-xs text-ua-red-600 hover:text-ua-red-700 font-medium">View all →</a>
            </div>

            @if($recentReleased->isEmpty())
                <div class="bg-white rounded-2xl border border-slate-200 p-6 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="file-output" class="w-6 h-6 text-slate-300"></i>
                    </div>
                    <p class="text-sm text-slate-400">No Travel Orders released yet.</p>
                    <a href="{{ route('records-office.outgoing') }}"
                       class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-ua-red-600 hover:text-ua-red-700">
                        <i data-lucide="arrow-right" class="w-4 h-4"></i> Go to Outgoing Register
                    </a>
                </div>
            @else
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    @foreach($recentReleased as $to)
                    <a href="{{ route('travel-orders.show', $to) }}"
                       class="flex items-start gap-3 px-4 py-3.5 border-b border-slate-50 last:border-b-0 hover:bg-slate-50 transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0 mt-0.5">
                            <i data-lucide="stamp" class="w-4 h-4 text-emerald-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-mono font-bold text-ua-red-700">{{ $to->to_number }}</p>
                                <span class="text-slate-300">·</span>
                                <p class="text-xs text-slate-500">{{ $to->department?->abbreviation ?? '—' }}</p>
                            </div>
                            <p class="text-sm font-medium text-slate-800 truncate mt-0.5">{{ $to->traveler?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $to->event_name }}</p>
                        </div>
                        <p class="text-[11px] text-slate-400 shrink-0 mt-0.5">{{ $to->records_released_at?->format('M j') ?? '—' }}</p>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Recent Incoming Logs --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-700">Recent Incoming Logs</h2>
                <a href="{{ route('records-office.incoming') }}" class="text-xs text-ua-red-600 hover:text-ua-red-700 font-medium">View all →</a>
            </div>

            @if($recentIncoming->isEmpty())
                <div class="bg-white rounded-2xl border border-slate-200 p-6 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="inbox" class="w-6 h-6 text-slate-300"></i>
                    </div>
                    <p class="text-sm text-slate-400">No invitations logged yet.</p>
                    <a href="{{ route('records-office.incoming') }}"
                       class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-ua-red-600 hover:text-ua-red-700">
                        <i data-lucide="plus" class="w-4 h-4"></i> Log an Invitation
                    </a>
                </div>
            @else
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    @foreach($recentIncoming as $inv)
                    <div class="flex items-start gap-3 px-4 py-3.5 border-b border-slate-50 last:border-b-0">
                        <div class="w-8 h-8 rounded-lg
                            {{ $inv->status === 'new' ? 'bg-amber-50' : ($inv->status === 'forwarded' ? 'bg-emerald-50' : 'bg-slate-50') }}
                            flex items-center justify-center shrink-0 mt-0.5">
                            <i data-lucide="{{ $inv->status === 'new' ? 'clock' : ($inv->status === 'forwarded' ? 'send' : 'x') }}"
                               class="w-4 h-4 {{ $inv->status === 'new' ? 'text-amber-500' : ($inv->status === 'forwarded' ? 'text-emerald-600' : 'text-slate-400') }}"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $inv->event_name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $inv->sender_org }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $inv->statusBadgeClass() }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $inv->received_at->format('M j') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
