<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'UA-TRaMP') — University of Antique</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen">
    @php
        $currentUser = auth()->user();
        $role = $currentUser?->role;
        $currentRoute = request()->route()?->getName();
    @endphp

    <div class="flex min-h-screen">
        {{-- ========================== SIDEBAR ========================== --}}
        <aside id="sidebar"
               class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out flex flex-col">

            {{-- Brand --}}
            <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-200">
                <div class="w-9 h-9 rounded-xl bg-ua-red-600 flex items-center justify-center shadow-sm">
                    <img src="{{ asset('images/ua-logo.png') }}" alt="UA" class="w-7 h-7 object-contain" onerror="this.style.display='none'">
                </div>
                <div>
                    <p class="text-sm font-bold leading-tight">UA-TRaMP</p>
                    <p class="text-[10px] text-slate-500 leading-tight uppercase tracking-wider">Travel Platform</p>
                </div>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
                <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Main</p>

                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                          {{ $currentRoute === 'dashboard' ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    <span>Dashboard</span>
                </a>

                @if($role === 'traveler')
                    <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-6 mb-2">Endorsements</p>

                    <a href="{{ route('endorsement-letters.staff') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                              {{ $currentRoute === 'endorsement-letters.staff' ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>My Endorsements</span>
                        @if(($myEndorsementsCount ?? 0) > 0)
                            <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-ua-red-600 text-white font-semibold">{{ $myEndorsementsCount }}</span>
                        @endif
                    </a>

                    <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-6 mb-2">Travel Orders</p>

                    <a href="{{ route('travel-orders.my') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                              {{ $currentRoute === 'travel-orders.my' ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        <span>My Travel Orders</span>
                    </a>

                    <a href="{{ route('travel-orders.personal.create') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                              {{ $currentRoute === 'travel-orders.personal.create' ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Request Travel Order</span>
                    </a>
                @endif

                @if($role === 'dean')
                    @php $isPres = $currentUser?->department?->abbreviation === 'PRES'; @endphp

                    @if($isPres)
                        {{-- President: inbox + forwarded invitations --}}
                        <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-6 mb-2">Invitations</p>

                        <a href="{{ route('received-invitations.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                                  {{ str_starts_with($currentRoute ?? '', 'received-invitations') ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            <i data-lucide="inbox" class="w-4 h-4"></i>
                            <span>Inbox</span>
                            @if(($pendingReceivedCount ?? 0) > 0)
                                <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-ua-red-600 text-white font-semibold">{{ $pendingReceivedCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('invitations.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                                  {{ str_starts_with($currentRoute ?? '', 'invitations') ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                            <span>Forwarded</span>
                            @if(($forwardedAwaitingCount ?? 0) > 0)
                                <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-amber-500 text-white font-semibold">{{ $forwardedAwaitingCount }}</span>
                            @endif
                        </a>

                        <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-4 mb-2">Travel Orders</p>

                        <a href="{{ route('president.travel-orders.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                                  {{ str_starts_with($currentRoute ?? '', 'president.travel-orders') ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            <i data-lucide="pen-tool" class="w-4 h-4"></i>
                            <span>Sign Travel Orders</span>
                            @if(($pendingSignatureCount ?? 0) > 0)
                                <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-ua-red-600 text-white font-semibold">{{ $pendingSignatureCount }}</span>
                            @endif
                        </a>
                    @else
                        {{-- College Dean: inbox + travel orders --}}
                        <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-6 mb-2">Invitations</p>

                        <a href="{{ route('invitations.inbox') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                                  {{ $currentRoute === 'invitations.inbox' || $currentRoute === 'invitations.show' ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            <i data-lucide="inbox" class="w-4 h-4"></i>
                            <span>Received Invitations</span>
                            @if(($deanInboxCount ?? 0) > 0)
                                <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-ua-red-600 text-white font-semibold">{{ $deanInboxCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('endorsement-letters.my') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                                  {{ str_starts_with($currentRoute ?? '', 'endorsement-letters') ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            <i data-lucide="file-signature" class="w-4 h-4"></i>
                            <span>My Endorsements</span>
                            @if(($deanEndorsementUpdatesCount ?? 0) > 0)
                                <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-500 text-white font-semibold">{{ $deanEndorsementUpdatesCount }}</span>
                            @endif
                        </a>

                        <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-4 mb-2">Travel Orders</p>

                        <a href="{{ route('travel-orders.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                                  {{ str_starts_with($currentRoute ?? '', 'travel-orders') && $currentRoute !== 'travel-orders.create' ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>My Travel Orders</span>
                        </a>

                        <a href="{{ route('travel-orders.create') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                                  {{ $currentRoute === 'travel-orders.create' ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>New Travel Order</span>
                        </a>
                    @endif
                @endif

                @if($role === 'approver')
                    <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-6 mb-2">Approvals</p>

                    <a href="{{ route('approvals.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                              {{ str_starts_with($currentRoute ?? '', 'approvals') ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        <span>Pending Approvals</span>
                        @if(($pendingApprovalsCount ?? 0) > 0)
                            <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-ua-red-600 text-white font-semibold">{{ $pendingApprovalsCount }}</span>
                        @endif
                    </a>

                    @if(in_array($currentUser?->approver_type, ['vp_academic', 'vp_research']))
                    <a href="{{ route('endorsement-letters.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                              {{ str_starts_with($currentRoute ?? '', 'endorsement-letters') ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i data-lucide="file-signature" class="w-4 h-4"></i>
                        <span>Endorsement Letters</span>
                        @if(($pendingEndorsementsCount ?? 0) > 0)
                            <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-ua-red-600 text-white font-semibold">{{ $pendingEndorsementsCount }}</span>
                        @endif
                    </a>
                    @endif

                @endif

                @if($role === 'records_officer')
                    <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-6 mb-2">Records Office</p>

                    <a href="{{ route('records-office.outgoing') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                              {{ $currentRoute === 'records-office.outgoing' ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i data-lucide="file-output" class="w-4 h-4"></i>
                        <span>Outgoing Register</span>
                        @if(($pendingReleaseCount ?? 0) > 0)
                            <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-amber-500 text-white font-semibold">{{ $pendingReleaseCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('records-office.incoming') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                              {{ $currentRoute === 'records-office.incoming' ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i data-lucide="file-input" class="w-4 h-4"></i>
                        <span>Incoming Register</span>
                    </a>
                @endif

                @if($role === 'admin')
                    <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-6 mb-2">Administration</p>

                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                              {{ str_starts_with($currentRoute ?? '', 'admin.users') ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>Users</span>
                        @if($pendingUsersCount > 0)
                            <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-amber-500 text-white font-semibold">{{ $pendingUsersCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('admin.departments.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                              {{ str_starts_with($currentRoute ?? '', 'admin.departments') ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i data-lucide="building-2" class="w-4 h-4"></i>
                        <span>Departments</span>
                    </a>

                    <a href="{{ route('admin.analytics.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                              {{ $currentRoute === 'admin.analytics.index' ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                        <span>Analytics</span>
                    </a>

                @endif

                <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-6 mb-2">Account</p>
                <a href="{{ route('profile.show') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                          {{ $currentRoute === 'profile.show' ? 'bg-ua-red-50 text-ua-red-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i data-lucide="user-circle-2" class="w-4 h-4"></i>
                    <span>My Profile</span>
                </a>
            </nav>

            {{-- User card --}}
            @if($currentUser)
                <div class="p-3 border-t border-slate-200">
                    <a href="{{ route('profile.show') }}"
                       class="flex items-center gap-3 p-2 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors">
                        <div class="w-9 h-9 rounded-full shrink-0 overflow-hidden border border-slate-200">
                            @if($currentUser->avatar)
                                <img src="{{ $currentUser->avatarUrl() }}" alt="{{ $currentUser->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-ua-red-500 to-ua-red-700 flex items-center justify-center text-white text-sm font-semibold">
                                    {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate">{{ $currentUser->name }}</p>
                            <p class="text-xs text-slate-500 truncate">
                                @if($currentUser->role === 'dean' && $currentUser->department?->abbreviation === 'PRES')
                                    {{ $currentUser->requested_position ?? 'University President' }}
                                @elseif($currentUser->role === 'records_officer')
                                    Records Officer
                                @else
                                    {{ ucfirst($currentUser->role) }}
                                @endif
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                    </a>
                </div>
            @endif
        </aside>

        {{-- Overlay for mobile --}}
        <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/50 z-30 hidden lg:hidden"></div>

        {{-- ========================== MAIN ========================== --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Topbar --}}
            <header class="sticky top-0 z-20 h-16 bg-white/80 backdrop-blur border-b border-slate-200 flex items-center px-4 sm:px-6 gap-3">
                <button id="sidebarToggle" class="lg:hidden p-2 rounded-lg hover:bg-slate-100">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>

                <div class="flex-1">
                    <p class="text-xs text-slate-500">@yield('eyebrow', 'UA-TRaMP')</p>
                    <h1 class="text-base font-semibold truncate flex items-center gap-2">
                        <span>@yield('page_title', 'Dashboard')</span>
                        @if(($unreadCount ?? 0) > 0)
                            <span class="relative flex h-2 w-2" title="You have {{ $unreadCount }} new notification(s)">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                            </span>
                        @endif
                    </h1>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Notification Bell --}}
                    @if($currentUser)
                        <div class="relative" id="notifWrapper">
                            <button id="notifToggle"
                                    class="relative p-2 rounded-lg hover:bg-slate-100 text-slate-600"
                                    aria-label="Notifications">
                                <i data-lucide="bell" class="w-5 h-5"></i>
                                @if($unreadCount > 0)
                                    <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-rose-500"></span>
                                @endif
                            </button>

                            <div id="notifPanel"
                                 class="hidden absolute right-0 mt-1 w-80 bg-white rounded-2xl border border-slate-200 shadow-xl z-50 overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                                    <p class="text-sm font-semibold">Notifications</p>
                                    @if($unreadCount > 0)
                                        <form method="POST" action="{{ route('notifications.readAll') }}">
                                            @csrf
                                            <button type="submit" class="text-xs text-ua-red-600 hover:text-ua-red-700 font-medium">
                                                Mark all read
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                @if($unreadNotifications->isEmpty())
                                    <div class="px-4 py-8 text-center">
                                        <i data-lucide="bell-off" class="w-6 h-6 text-slate-300 mx-auto mb-2"></i>
                                        <p class="text-xs text-slate-400">No new notifications</p>
                                    </div>
                                @else
                                    <ul class="divide-y divide-slate-50 max-h-72 overflow-y-auto">
                                        @foreach($unreadNotifications as $notif)
                                            @php
                                                $data = $notif->data;
                                                $iconMap = [
                                                    'trip'                  => ['icon' => 'plane',           'color' => 'text-indigo-600 bg-indigo-50'],
                                                    'approval'              => ['icon' => 'check-circle-2',  'color' => 'text-ua-red-600 bg-ua-red-50'],
                                                    'decision'              => ['icon' => ($data['action'] ?? '') === 'approved' ? 'check-circle-2' : 'x-circle',
                                                                                'color' => ($data['action'] ?? '') === 'approved' ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50'],
                                                    'user'                  => ['icon' => 'user-plus',       'color' => 'text-amber-600 bg-amber-50'],
                                                    'escalation'            => ['icon' => 'alert-triangle',  'color' => 'text-amber-600 bg-amber-50'],
                                                    'endorsement_assigned'  => ['icon' => 'user-check',      'color' => 'text-indigo-600 bg-indigo-50'],
                                                    'endorsement_reviewed'  => ['icon' => 'file-signature',  'color' => 'text-emerald-600 bg-emerald-50'],
                                                    'endorsement_approved'  => ['icon' => 'check-circle-2',  'color' => 'text-emerald-600 bg-emerald-50'],
                                                    'endorsement_submitted' => ['icon' => 'send',            'color' => 'text-indigo-600 bg-indigo-50'],
                                                ];
                                                $ic = $iconMap[$data['type'] ?? ''] ?? ['icon' => 'bell', 'color' => 'text-slate-600 bg-slate-100'];
                                            @endphp
                                            <li>
                                                <form method="POST" action="{{ route('notifications.read', $notif->id) }}" class="block">
                                                    @csrf
                                                    <button type="submit" class="w-full text-left flex items-start gap-3 px-4 py-3 hover:bg-slate-50">
                                                        <div class="w-8 h-8 rounded-full {{ $ic['color'] }} flex items-center justify-center shrink-0 mt-0.5">
                                                            <i data-lucide="{{ $ic['icon'] }}" class="w-4 h-4"></i>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-xs text-slate-700 leading-snug">{{ $data['message'] ?? '' }}</p>
                                                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
                                                        </div>
                                                    </button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($currentUser)
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                <span class="hidden sm:inline">Logout</span>
                            </button>
                        </form>
                    @endif
                </div>
            </header>

            {{-- Content --}}
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-full">
                {{-- Flash messages --}}
                @if(session('success'))
                    <div class="mb-4 flex items-start gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 mt-0.5"></i>
                        <p class="text-sm text-emerald-800">{{ session('success') }}</p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 flex items-start gap-3 p-4 bg-rose-50 border border-rose-200 rounded-xl">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 mt-0.5"></i>
                        <p class="text-sm text-rose-800">{{ session('error') }}</p>
                    </div>
                @endif

                @if(session('compliance_warning'))
                    <div class="mb-4 flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 mt-0.5"></i>
                        <p class="text-sm text-amber-800">{{ session('compliance_warning') }}</p>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 flex items-start gap-3 p-4 bg-rose-50 border border-rose-200 rounded-xl">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 mt-0.5"></i>
                        <div class="text-sm text-rose-800">
                            <p class="font-medium mb-1">Please fix the following:</p>
                            <ul class="list-disc ps-5 space-y-0.5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>

            <footer class="px-4 sm:px-6 lg:px-8 py-4 text-xs text-slate-400 border-t border-slate-200 text-center">
                &copy; {{ date('Y') }} University of Antique — Travel Management & Itinerary Platform
            </footer>
        </div>
    </div>

    @stack('scripts')
    <script>
        // Mobile sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggle = document.getElementById('sidebarToggle');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }
        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
        toggle?.addEventListener('click', () => {
            if (sidebar.classList.contains('-translate-x-full')) openSidebar();
            else closeSidebar();
        });
        overlay?.addEventListener('click', closeSidebar);

        // Notification bell toggle
        const notifToggle  = document.getElementById('notifToggle');
        const notifPanel   = document.getElementById('notifPanel');
        const notifWrapper = document.getElementById('notifWrapper');
        notifToggle?.addEventListener('click', (e) => {
            e.stopPropagation();
            notifPanel.classList.toggle('hidden');
        });
        document.addEventListener('click', (e) => {
            if (notifWrapper && !notifWrapper.contains(e.target)) {
                notifPanel?.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
