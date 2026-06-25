<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UA-TRaMP — University of Antique</title>

    @vite(['resources/css/app.css'])
    <script src="{{ asset('js/lucide.min.js') }}"></script>
</head>
<body class="font-sans antialiased">

    <div class="relative min-h-screen overflow-hidden bg-gradient-to-b from-[#f5e86a] via-[#e9d843] to-[#d1c100]">

        {{-- decorative bamboo --}}
        <img src="{{ asset('images/bamboo.png') }}" alt="" class="pointer-events-none absolute left-0 top-0 h-[120vh] w-[480px] object-contain opacity-30 -translate-x-20">
        <img src="{{ asset('images/bamboo.png') }}" alt="" class="pointer-events-none absolute right-0 top-0 h-[120vh] w-[480px] object-contain opacity-30 scale-x-[-1] translate-x-20">

        {{-- top nav --}}
        <header class="relative z-10 flex items-center justify-between px-6 sm:px-10 py-6">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-ua-red-600 flex items-center justify-center shadow">
                    <img src="{{ asset('images/ua-logo.png') }}" alt="UA" class="w-8 h-8 object-contain" onerror="this.style.display='none'">
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">UA-TRaMP</p>
                    <p class="text-[10px] text-slate-700 uppercase tracking-wider">University of Antique</p>
                </div>
            </div>

            <a href="{{ route('login') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-900 rounded-full text-sm font-semibold border-2 border-slate-900 shadow-sm">
                Sign In
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </header>

        {{-- hero --}}
        <section class="relative z-10 flex flex-col items-center justify-center text-center px-6 pt-12 pb-32">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/60 backdrop-blur border border-slate-900/10 text-xs font-semibold text-slate-800 mb-6">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Travel Management Platform
            </span>

            <h1 class="font-[Hanalei] text-7xl sm:text-8xl md:text-[8.5rem] leading-none tracking-[0.08em] text-[#d7c500] mb-6"
                style="-webkit-text-stroke: 4px #111111; text-shadow: 0 4px 0 rgba(0,0,0,0.15);">
                BAMBUHAY!
            </h1>

            <p class="max-w-2xl text-base sm:text-lg text-slate-800 mb-10 leading-relaxed">
                The modern travel platform for <strong>University of Antique</strong> —
                assign trips by department, track documents via QR codes,
                and approve travel in real-time.
            </p>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-white hover:bg-slate-50 text-slate-900 rounded-full text-base font-semibold border-2 border-slate-900 shadow-lg">
                    Log In
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                </a>
                <a href="#features"
                   class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-slate-900 hover:bg-slate-800 text-white rounded-full text-base font-semibold shadow-lg">
                    Learn More
                    <i data-lucide="arrow-down" class="w-4 h-4"></i>
                </a>
            </div>
        </section>

        {{-- feature strip --}}
        <section id="features" class="relative z-10 px-6 sm:px-10 pb-40">
            <div class="max-w-5xl mx-auto grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl bg-white/80 backdrop-blur border border-white shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-ua-red-50 flex items-center justify-center mb-3">
                        <i data-lucide="plane" class="w-5 h-5 text-ua-red-600"></i>
                    </div>
                    <p class="font-semibold text-slate-900 mb-1">Smart Assignment</p>
                    <p class="text-xs text-slate-600">Assign trips by department with pre-filled forms.</p>
                </div>
                <div class="p-5 rounded-2xl bg-white/80 backdrop-blur border border-white shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-ua-red-50 flex items-center justify-center mb-3">
                        <i data-lucide="scan-line" class="w-5 h-5 text-ua-red-600"></i>
                    </div>
                    <p class="font-semibold text-slate-900 mb-1">QR Document Tracing</p>
                    <p class="text-xs text-slate-600">Track requests, approvals & liquidation via QR.</p>
                </div>
                <div class="p-5 rounded-2xl bg-white/80 backdrop-blur border border-white shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-ua-red-50 flex items-center justify-center mb-3">
                        <i data-lucide="layout-dashboard" class="w-5 h-5 text-ua-red-600"></i>
                    </div>
                    <p class="font-semibold text-slate-900 mb-1">Real-time Dashboards</p>
                    <p class="text-xs text-slate-600">Status, timelines & bottleneck insights.</p>
                </div>
                <div class="p-5 rounded-2xl bg-white/80 backdrop-blur border border-white shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-ua-red-50 flex items-center justify-center mb-3">
                        <i data-lucide="shield-check" class="w-5 h-5 text-ua-red-600"></i>
                    </div>
                    <p class="font-semibold text-slate-900 mb-1">Multi-level Approvals</p>
                    <p class="text-xs text-slate-600">Policy-compliant with full audit trails.</p>
                </div>
            </div>
        </section>

        {{-- bottom red band with logo --}}
        <div class="absolute left-0 right-0 bottom-0">
            <div class="h-32 bg-ua-red-600 rounded-t-[3rem]"></div>
            <div class="absolute left-1/2 -translate-x-1/2 -top-16 w-28 h-28 rounded-full bg-white border-4 border-ua-red-600 shadow-lg flex items-center justify-center">
                <img src="{{ asset('images/ua-logo.png') }}" alt="UA Logo" class="w-20 h-20 object-contain">
            </div>
        </div>
    </div>

    <script>
        if (window.lucide) window.lucide.createIcons();
    </script>
</body>
</html>
