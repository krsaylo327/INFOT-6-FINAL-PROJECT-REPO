<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Order Verification — {{ $data['to_number'] }}</title>
    @vite(['resources/css/app.css'])
    <script src="{{ asset('js/lucide.min.js') }}"></script>
</head>
<body class="bg-slate-50 min-h-screen">
    <div class="max-w-2xl mx-auto py-10 px-4">

        {{-- Header --}}
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-ua-red-600 text-white mb-3">
                <i data-lucide="badge-check" class="w-6 h-6"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Travel Order Verification</h1>
            <p class="text-sm text-slate-500 mt-1">University of Antique — Checkpoint Verification</p>
        </div>

        {{-- Verified/Invalid card --}}
        @php
            $valid = $data['is_valid'];
            $headerColor = $valid ? 'from-emerald-500 to-emerald-600' : 'from-rose-500 to-rose-600';
        @endphp
        <div class="bg-white rounded-2xl border-2 {{ $valid ? 'border-emerald-200' : 'border-rose-200' }} overflow-hidden shadow-sm">
            <div class="bg-gradient-to-r {{ $headerColor }} px-6 py-5 text-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                        <i data-lucide="{{ $valid ? 'check-circle-2' : 'x-circle' }}" class="w-6 h-6"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-white/80 uppercase tracking-wide font-semibold">{{ $valid ? 'Authentic' : 'Invalid' }}</p>
                        <h2 class="text-lg font-bold">Travel Order {{ $data['to_number'] }}</h2>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-white/20 text-white uppercase tracking-wider">
                        {{ $data['status'] }}
                    </span>
                </div>
            </div>

            <div class="p-6 space-y-5">
                {{-- Event details --}}
                <div>
                    <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Event</p>
                    <p class="text-base font-semibold text-slate-900">{{ $data['event_name'] }}</p>
                    <p class="text-sm text-slate-500">{{ $data['venue'] }}@if($data['destination']), {{ $data['destination'] }}@endif</p>
                    <p class="text-sm text-slate-500">{{ $data['formatted_dates'] }}</p>
                </div>

                {{-- Travelers --}}
                <div class="border-t border-slate-100 pt-4">
                    <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-2">
                        Authorized {{ Str::plural('Traveler', $data['travelers']->count()) }} ({{ $data['travelers']->count() }})
                    </p>
                    <ul class="space-y-2">
                        @foreach($data['travelers'] as $t)
                        <li class="flex items-center gap-2 text-sm">
                            <i data-lucide="user-check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                            <div>
                                <span class="font-medium text-slate-800">{{ $t['name'] }}</span>
                                @if($t['position'])
                                    <span class="text-slate-500"> — {{ $t['position'] }}</span>
                                @endif
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Authorization info --}}
                <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                    <div>
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Department</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $data['department'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Type</p>
                        <p class="text-sm font-semibold text-slate-900">{{ ucfirst($data['type']) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Endorsed By (Dean)</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $data['dean'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Issued By (President)</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $data['issued_by'] ?? '—' }}</p>
                        <p class="text-xs text-slate-500">{{ $data['issued_at'] }}</p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-start gap-2">
                <i data-lucide="info" class="w-4 h-4 text-slate-500 mt-0.5 shrink-0"></i>
                <p class="text-xs text-slate-600 leading-relaxed">
                    This Travel Order has been verified against the University of Antique Travel Management Platform.
                    Status as of {{ $data['scanned_at'] }}: <strong>{{ ucfirst($data['status']) }}</strong>.
                    For inquiries, contact the Office of the President.
                </p>
            </div>
        </div>

        <p class="text-center text-xs text-slate-400 mt-4">© {{ date('Y') }} University of Antique — UA-TRaMP</p>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
