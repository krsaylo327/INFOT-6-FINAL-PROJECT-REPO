<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Pending — UA-TRaMP</title>
    @vite(['resources/css/app.css'])
    <script src="{{ asset('js/lucide.min.js') }}"></script>
</head>
<body class="font-sans antialiased bg-slate-50 min-h-screen">
    <div class="min-h-screen grid lg:grid-cols-2">
        <aside class="relative hidden lg:flex flex-col justify-between p-10 text-white overflow-hidden bg-gradient-to-br from-ua-red-600 via-ua-red-700 to-ua-red-900">
            <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-40 -right-20 w-[28rem] h-[28rem] rounded-full bg-white/5"></div>
            <div class="absolute top-40 right-20 w-40 h-40 rounded-full bg-ua-yellow/10"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-white/10 backdrop-blur flex items-center justify-center">
                    <img src="{{ asset('images/ua-logo.png') }}" alt="UA Logo" class="w-8 h-8 object-contain" onerror="this.style.display='none'">
                </div>
                <div>
                    <p class="text-sm font-semibold">University of Antique</p>
                    <p class="text-xs text-white/70">Official Travel Platform</p>
                </div>
            </div>
            <div class="relative">
                <p class="text-xs uppercase tracking-[0.3em] text-white/70 mb-3">UA-TRaMP</p>
                <h1 class="text-5xl xl:text-6xl font-extrabold leading-tight mb-5">
                    Almost <span class="text-ua-yellow">there.</span>
                </h1>
                <p class="text-base text-white/85 max-w-md leading-relaxed">
                    Your account has been submitted. An administrator will review and activate it shortly.
                </p>
            </div>
            <p class="relative text-xs text-white/60">
                &copy; {{ date('Y') }} University of Antique. All rights reserved.
            </p>
        </aside>
        <main class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md text-center">
                <div class="lg:hidden flex items-center justify-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-ua-red-600 flex items-center justify-center">
                        <img src="{{ asset('images/ua-logo.png') }}" alt="UA" class="w-7 h-7 object-contain" onerror="this.style.display='none'">
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-bold">UA-TRaMP</p>
                        <p class="text-xs text-slate-500">University of Antique</p>
                    </div>
                </div>

                <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="clock" class="w-8 h-8 text-amber-500"></i>
                </div>

                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-2">Account pending approval</h2>
                <p class="text-sm text-slate-500 mb-8">
                    Hi <span class="font-semibold text-slate-700">{{ auth()->user()->name }}</span>, your account has been created successfully.
                    An administrator needs to approve it before you can access the platform.
                </p>

                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-left mb-8">
                    <div class="flex items-start gap-3">
                        <i data-lucide="info" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
                        <div class="text-xs text-amber-800 space-y-1">
                            <p class="font-semibold">What happens next?</p>
                            <ul class="list-disc list-inside space-y-0.5 text-amber-700">
                                <li>An admin will review your registration details</li>
                                <li>You'll be notified once your account is activated</li>
                                <li>After activation, you can access all platform features</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        Sign out
                    </button>
                </form>
            </div>
        </main>
    </div>
    <script>
        if (window.lucide) window.lucide.createIcons();
    </script>
</body>
</html>
