<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — UA-TRaMP</title>

    @vite(['resources/css/app.css'])
    <script src="{{ asset('js/lucide.min.js') }}"></script>
</head>
<body class="font-sans antialiased bg-slate-50 min-h-screen">

    <div class="min-h-screen grid lg:grid-cols-2">
        {{-- ====== Left: Brand panel ====== --}}
        <aside class="relative hidden lg:flex flex-col justify-between p-10 text-white overflow-hidden bg-gradient-to-br from-ua-red-600 via-ua-red-700 to-ua-red-900">
            {{-- decorative shapes --}}
            <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-40 -right-20 w-[28rem] h-[28rem] rounded-full bg-white/5"></div>
            <div class="absolute top-40 right-20 w-40 h-40 rounded-full bg-ua-yellow/10"></div>

            {{-- top logo --}}
            <div class="relative flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl bg-white/10 backdrop-blur flex items-center justify-center">
                    <img src="{{ asset('images/ua-logo.png') }}" alt="UA Logo" class="w-10 h-10 object-contain" onerror="this.style.display='none'">
                </div>
                <div>
                    <p class="text-lg font-bold">University of Antique</p>
                    <p class="text-xs text-white/70">Official Travel Platform</p>
                </div>
            </div>

            {{-- middle content --}}
            <div class="relative">
                <p class="text-xs uppercase tracking-[0.3em] text-white/70 mb-3">UA-TRaMP</p>
                <h1 class="text-5xl xl:text-6xl font-extrabold leading-tight mb-5">
                    Travel, <br>
                    <span class="text-ua-yellow">simplified.</span>
                </h1>
                <p class="text-base text-white/85 max-w-md leading-relaxed">
                    Assign, track, and approve university travel requests in one place.
                    Faster itineraries, fewer forms, real-time visibility.
                </p>

                <div class="mt-8 grid grid-cols-3 gap-3 max-w-md">
                    <div class="p-3 rounded-xl bg-white/10 backdrop-blur">
                        <i data-lucide="plane" class="w-5 h-5 text-ua-yellow mb-1"></i>
                        <p class="text-xs font-medium">Smart Requests</p>
                    </div>
                    <div class="p-3 rounded-xl bg-white/10 backdrop-blur">
                        <i data-lucide="scan-line" class="w-5 h-5 text-ua-yellow mb-1"></i>
                        <p class="text-xs font-medium">QR Tracing</p>
                    </div>
                    <div class="p-3 rounded-xl bg-white/10 backdrop-blur">
                        <i data-lucide="bar-chart-3" class="w-5 h-5 text-ua-yellow mb-1"></i>
                        <p class="text-xs font-medium">Analytics</p>
                    </div>
                </div>
            </div>

            <p class="relative text-xs text-white/60">
                &copy; {{ date('Y') }} University of Antique. All rights reserved.
            </p>
        </aside>

        {{-- ====== Right: Login card ====== --}}
        <main class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md">
                {{-- Mobile header --}}
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-ua-red-600 flex items-center justify-center">
                        <img src="{{ asset('images/ua-logo.png') }}" alt="UA" class="w-7 h-7 object-contain" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <p class="text-sm font-bold">UA-TRaMP</p>
                        <p class="text-xs text-slate-500">University of Antique</p>
                    </div>
                </div>

                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-1">Welcome back</h2>
                <p class="text-sm text-slate-500 mb-8">Sign in with your university account to continue.</p>

                {{-- Success flash (e.g. after registration) --}}
                @if(session('success'))
                    <div class="mb-4 flex items-start gap-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 mt-0.5 shrink-0"></i>
                        <p class="text-sm text-emerald-800">{{ session('success') }}</p>
                    </div>
                @endif

                {{-- Errors --}}
                @if($errors->any())
                    <div class="mb-4 flex items-start gap-3 p-3 bg-rose-50 border border-rose-200 rounded-xl">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 mt-0.5 shrink-0"></i>
                        <ul class="text-sm text-rose-800 space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Google Sign-In button (decorative for now) --}}
                <button type="button"
                        onclick="alert('Google Sign-In will be enabled after Google Cloud credentials are configured.')"
                        class="w-full flex items-center justify-center gap-3 px-4 py-3 mb-4 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 hover:shadow-sm">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M23.49 12.27c0-.82-.07-1.63-.22-2.41H12v4.56h6.47a5.54 5.54 0 0 1-2.4 3.63v3h3.87a11.67 11.67 0 0 0 3.55-8.78Z" fill="#4285F4"/>
                        <path d="M12 24c3.24 0 5.96-1.07 7.95-2.91l-3.87-3a7.24 7.24 0 0 1-10.78-3.78H1.32v3.09A12 12 0 0 0 12 24Z" fill="#34A853"/>
                        <path d="M5.3 14.31a7.24 7.24 0 0 1 0-4.62V6.6H1.32a12 12 0 0 0 0 10.8L5.3 14.3Z" fill="#FBBC05"/>
                        <path d="M12 4.75a6.5 6.5 0 0 1 4.6 1.8l3.44-3.44A11.56 11.56 0 0 0 12 0 12 12 0 0 0 1.32 6.6L5.3 9.69A7.24 7.24 0 0 1 12 4.75Z" fill="#EA4335"/>
                    </svg>
                    Sign in with Google
                    <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500">soon</span>
                </button>

                <div class="flex items-center gap-3 my-6">
                    <div class="h-px bg-slate-200 flex-1"></div>
                    <span class="text-xs uppercase tracking-wider text-slate-400">or with email</span>
                    <div class="h-px bg-slate-200 flex-1"></div>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">University Email</label>
                        <div class="relative">
                            <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   placeholder="name@antiquespride.edu.ph"
                                   class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="password" name="password" required
                                   placeholder="••••••••"
                                   class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-xl text-sm font-semibold shadow-sm">
                        Sign In
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </form>

                <p class="mt-6 text-xs text-slate-500 text-center">
                    Don't have an account? <a href="{{ route('register') }}" class="font-semibold text-ua-red-600 hover:text-ua-red-700">Sign up</a>
                </p>
                <p class="mt-2 text-xs text-slate-500 text-center">
                    Only <span class="font-semibold text-slate-700">@antiquespride.edu.ph</span> accounts are allowed.
                </p>
            </div>
        </main>
    </div>

    <script>
        if (window.lucide) window.lucide.createIcons();
    </script>
</body>
</html>
