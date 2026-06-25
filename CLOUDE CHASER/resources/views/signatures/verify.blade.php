<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signature Verification — UA-TRaMP</title>
    @vite(['resources/css/app.css'])
    <script src="{{ asset('js/lucide.min.js') }}"></script>
</head>
<body class="bg-slate-50 min-h-screen">
    <div class="max-w-2xl mx-auto py-12 px-4">

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-ua-red-600 text-white mb-3">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Signature Verification</h1>
            <p class="text-sm text-slate-500 mt-1">University of Antique — Travel Management Platform</p>
        </div>

        @if($signature)
            {{-- Verified Card --}}
            <div class="bg-white rounded-2xl border-2 border-emerald-200 overflow-hidden shadow-sm">
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-5 text-white">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-sm text-white/80 uppercase tracking-wide font-semibold">Verified</p>
                            <h2 class="text-lg font-bold">Authentic Digital Signature</h2>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-5">
                    {{-- Signature image --}}
                    <div class="border border-slate-200 rounded-xl p-4 bg-slate-50">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-2">Signature</p>
                        <div class="bg-white border border-slate-200 rounded-lg p-3 inline-block">
                            <img src="{{ route('signatures.verify.image', $signature->verification_code) }}"
                                 alt="Signature" class="h-20 w-auto max-w-xs object-contain">
                        </div>
                    </div>

                    {{-- Signer info --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Signed By</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $signature->signer_name_snapshot }}</p>
                            <p class="text-xs text-slate-500">{{ $signature->signer_position_snapshot ?? '—' }}</p>
                            @if($signature->signer?->department)
                                <p class="text-xs text-slate-500">{{ $signature->signer->department->name }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Signed On</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $signature->signed_at->format('F j, Y') }}</p>
                            <p class="text-xs text-slate-500">{{ $signature->signed_at->format('g:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Purpose</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $signature->purposeLabel() }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Decision</p>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $signature->decisionBadgeClass() }}">
                                {{ ucfirst($signature->decision ?? '—') }}
                            </span>
                        </div>
                    </div>

                    @if($signature->decision_remarks)
                    <div class="border-t border-slate-100 pt-4">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Remarks</p>
                        <p class="text-sm text-slate-700 italic">"{{ $signature->decision_remarks }}"</p>
                    </div>
                    @endif

                    {{-- Document fingerprint --}}
                    <div class="border-t border-slate-100 pt-4">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-2">Document Fingerprint (SHA-256)</p>
                        <code class="block bg-slate-900 text-emerald-300 text-[10px] p-3 rounded-lg font-mono break-all leading-relaxed">{{ $signature->document_hash }}</code>
                        <p class="text-[10px] text-slate-400 mt-1.5 italic">
                            This unique fingerprint was computed from the document at the time of signing. Any modification to the document would produce a different hash, revealing tampering.
                        </p>
                    </div>

                    {{-- Verification code + QR --}}
                    <div class="border-t border-slate-100 pt-4 flex items-start justify-between gap-4 flex-wrap">
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Verification Code</p>
                            <code class="text-sm font-mono font-bold text-ua-red-700 tracking-wider">{{ $signature->verification_code }}</code>
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mt-3 mb-1">Signed From IP</p>
                            <code class="text-xs font-mono text-slate-600">{{ $signature->ip_address ?? '—' }}</code>
                        </div>
                        <div class="text-center shrink-0">
                            <div class="bg-white border-2 border-emerald-300 rounded-lg p-2 inline-block">
                                <img src="{{ route('signatures.verify.qr', $signature->verification_code) }}"
                                     alt="Verification QR Code" class="w-28 h-28">
                            </div>
                            <p class="text-[10px] text-emerald-700 font-semibold uppercase tracking-wider mt-1">
                                <i data-lucide="qr-code" class="w-3 h-3 inline"></i>
                                Scan to Re-Verify
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-start gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-slate-500 mt-0.5 shrink-0"></i>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        This signature is recorded in the official University of Antique Travel Management Platform.
                        The signature image, signer details, and document fingerprint cannot be altered after signing.
                        For inquiries, contact the Records Office at the President's Office.
                    </p>
                </div>
            </div>

        @else
            {{-- Not Found Card --}}
            <div class="bg-white rounded-2xl border-2 border-rose-200 overflow-hidden shadow-sm">
                <div class="bg-gradient-to-r from-rose-500 to-rose-600 px-6 py-5 text-white">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <i data-lucide="x-circle" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-sm text-white/80 uppercase tracking-wide font-semibold">Not Found</p>
                            <h2 class="text-lg font-bold">Invalid Verification Code</h2>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-sm text-slate-700">
                        The verification code <code class="font-mono font-bold text-ua-red-700">{{ $code }}</code> does not match any signature in our records.
                    </p>
                    <p class="text-xs text-slate-500 mt-3">
                        This could mean: the code was mistyped, the signature has been removed, or this is a forged document. Please verify the original source.
                    </p>
                </div>
            </div>
        @endif

        <p class="text-center text-xs text-slate-400 mt-6">
            © {{ date('Y') }} University of Antique — Travel Management Platform
        </p>
    </div>

    <script> lucide.createIcons(); </script>
</body>
</html>
