<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — UA-TRaMP</title>
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
                    Join <span class="text-ua-yellow">today.</span>
                </h1>
                <p class="text-base text-white/85 max-w-md leading-relaxed">
                    Create your account to start managing travel requests. Your registration will be reviewed by an administrator.
                </p>
            </div>
            <p class="relative text-xs text-white/60">
                &copy; {{ date('Y') }} University of Antique. All rights reserved.
            </p>
        </aside>
        <main class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md">
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-ua-red-600 flex items-center justify-center">
                        <img src="{{ asset('images/ua-logo.png') }}" alt="UA" class="w-7 h-7 object-contain" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <p class="text-sm font-bold">UA-TRaMP</p>
                        <p class="text-xs text-slate-500">University of Antique</p>
                    </div>
                </div>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-1">Create account</h2>
                <p class="text-sm text-slate-500 mb-8">Fill out the form below. Your account needs admin approval.</p>
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
                <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Full Name</label>
                        <div class="relative">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="name" value="{{ old('name') }}" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Email</label>
                        <div class="relative">
                            <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="email" name="email" value="{{ old('email') }}" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Employee/Faculty ID</label>
                        <div class="relative">
                            <i data-lucide="id-card" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="employee_id" value="{{ old('employee_id') }}" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400" placeholder="UA-2025-0123">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Department</label>
                        <select name="department_id" required class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                            <option value="">Select department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Position/Designation</label>
                        <div class="relative">
                            <i data-lucide="briefcase" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="requested_position" value="{{ old('requested_position') }}" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400" placeholder="e.g. Instructor, Dean, VP Academic">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Contact Number (optional)</label>
                        <div class="relative">
                            <i data-lucide="phone" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="tel" name="contact_number" value="{{ old('contact_number') }}" class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400" placeholder="0917 123 4567">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="password" name="password" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Confirm Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="password" name="password_confirmation" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                        </div>
                    </div>
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-xl text-sm font-semibold shadow-sm">
                        Create Account
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </form>
                <p class="mt-6 text-xs text-slate-500 text-center">
                    Already have an account? <a href="{{ route('login') }}" class="font-semibold text-ua-red-600 hover:text-ua-red-700">Sign in</a>
                </p>
                <div class="mt-8 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <i data-lucide="info" class="w-5 h-5 text-amber-600 inline-block mr-2 align-middle"></i>
                    <span class="text-xs text-amber-800 leading-relaxed">
                        Your account will be <strong>pending admin approval</strong>. You can log in but features are restricted until approved.
                    </span>
                </div>
            </div>
        </main>
    </div>
    <script>
        if (window.lucide) window.lucide.createIcons();
    </script>
</body>
</html>
