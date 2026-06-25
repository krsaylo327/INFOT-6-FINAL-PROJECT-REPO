@extends('layouts.app')

@section('title', 'My Profile')
@section('eyebrow', 'Account')
@section('page_title', 'My Profile')

@section('content')
<div class="max-w-3xl space-y-6">

    {{-- ── Profile card ── --}}
    <div class="bg-white rounded-2xl border border-slate-200">
        {{-- Cover band --}}
        <div class="h-28 rounded-t-2xl bg-gradient-to-r from-ua-red-600 to-ua-red-800"></div>

        {{-- Avatar row --}}
        <div class="px-6 pb-6">
            {{-- Avatar (overlaps band) + badges aligned to its bottom --}}
            <div class="flex items-end justify-between gap-4 -mt-10">
                <div class="relative shrink-0">
                    @if($user->avatar)
                        <img src="{{ $user->avatarUrl() }}"
                             alt="{{ $user->name }}"
                             class="w-20 h-20 rounded-2xl object-cover border-4 border-white shadow-md">
                    @else
                        <div class="w-20 h-20 rounded-2xl border-4 border-white shadow-md bg-gradient-to-br from-ua-red-500 to-ua-red-700 flex items-center justify-center text-3xl font-bold text-white select-none">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif

                    {{-- Camera upload overlay --}}
                    <label for="avatarInput"
                           class="absolute -bottom-1.5 -right-1.5 w-7 h-7 rounded-full bg-white border border-slate-200 shadow flex items-center justify-center cursor-pointer hover:bg-slate-50"
                           title="Change photo">
                        <i data-lucide="camera" class="w-3.5 h-3.5 text-slate-600"></i>
                    </label>
                </div>

                {{-- Badges --}}
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    @php
                        $roleColors = [
                            'admin'    => 'bg-ua-red-50 text-ua-red-700 border border-ua-red-200',
                            'approver' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                            'traveler' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                        ];
                        $statusColors = [
                            'active'   => 'bg-emerald-50 text-emerald-700',
                            'pending'  => 'bg-amber-50 text-amber-700',
                            'rejected' => 'bg-rose-50 text-rose-700',
                        ];
                    @endphp
                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $roleColors[$user->role] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ ucfirst($user->role) }}
                    </span>
                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $statusColors[$user->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ ucfirst($user->status) }}
                    </span>
                </div>
            </div>

            {{-- Name + email, on white below the band --}}
            <div class="mt-3 min-w-0">
                <h2 class="text-xl font-bold text-slate-900 truncate">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500 truncate">{{ $user->email }}</p>
            </div>

            {{-- Info grid --}}
            <dl class="mt-6 grid sm:grid-cols-2 gap-x-8 gap-y-4 border-t border-slate-100 pt-5">
                <div>
                    <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Employee ID</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $user->employee_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Department</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-800">
                        @if($user->department)
                            <span class="inline-flex items-center gap-1.5">
                                @if($user->department->abbreviation)
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono">{{ $user->department->abbreviation }}</span>
                                @endif
                                {{ $user->department->name }}
                            </span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Position</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $user->requested_position ?? '—' }}</dd>
                </div>
                @if($user->role === 'approver')
                <div>
                    <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Approver Level</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-800">Level {{ $user->approver_level ?? '—' }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Contact Number</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $user->contact_number ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Member Since</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $user->created_at->format('F j, Y') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Hidden avatar upload form (triggered by camera button) --}}
    <form id="avatarForm" method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" class="hidden">
        @csrf
        <input id="avatarInput" type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
    </form>

    {{-- ── Edit contact number ── --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center gap-2">
            <i data-lucide="edit-3" class="w-4 h-4 text-slate-500"></i>
            <h3 class="font-semibold text-slate-900">Edit Contact Details</h3>
        </div>
        <form method="POST" action="{{ route('profile.update') }}" class="p-5 sm:p-6 space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Contact Number</label>
                <input type="text" name="contact_number"
                       value="{{ old('contact_number', $user->contact_number) }}"
                       placeholder="e.g. +63 912 345 6789"
                       class="w-full sm:max-w-xs px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                @error('contact_number')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-400 mt-1">Contact your admin to update your name, email, department, or role.</p>
            </div>

            <div>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-xl text-sm font-semibold shadow-sm">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- ── Digital Signature (not needed for admins — they don't sign documents) ── --}}
    @if($user->role !== 'admin')
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <i data-lucide="pen-tool" class="w-4 h-4 text-slate-500"></i>
                <h3 class="font-semibold text-slate-900">Digital Signature</h3>
            </div>
            @if($user->hasSignature())
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700">
                    <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                    Active
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700">
                    <i data-lucide="alert-circle" class="w-3 h-3"></i>
                    Not Set
                </span>
            @endif
        </div>

        <div class="p-5 sm:p-6 space-y-4">
            <p class="text-xs text-slate-500 leading-relaxed">
                Your digital signature will be automatically applied to documents you sign electronically (endorsement letters, travel order approvals, etc.).
                Each signing creates a tamper-evident record with a unique verification code that can be checked publicly.
            </p>

            {{-- Current signature display --}}
            @if($user->hasSignature())
            <div class="border border-slate-200 rounded-xl p-4 bg-slate-50">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Current Signature</p>
                <div class="bg-white border border-slate-200 rounded-lg p-3 inline-block">
                    <img src="{{ $user->signatureUrl() }}" alt="Your signature" class="h-20 w-auto max-w-xs object-contain">
                </div>
                <p class="text-[11px] text-slate-400 mt-2">
                    <i data-lucide="clock" class="w-3 h-3 inline"></i>
                    Registered {{ $user->signature_uploaded_at?->format('F j, Y, g:i A') ?? '—' }}
                </p>
                <form method="POST" action="{{ route('profile.signature.delete') }}" class="mt-3"
                      onsubmit="return confirm('Remove your current signature? You will need to register a new one before signing documents.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-rose-600 hover:text-rose-700 font-medium inline-flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        Remove Signature
                    </button>
                </form>
            </div>
            @endif

            {{-- Tabs: Draw OR Upload --}}
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="flex border-b border-slate-200 bg-slate-50">
                    <button type="button" id="tab-draw" onclick="switchSigTab('draw')"
                            class="flex-1 px-4 py-2.5 text-xs font-semibold text-ua-red-700 bg-white border-b-2 border-ua-red-600">
                        <i data-lucide="pen-tool" class="w-3.5 h-3.5 inline"></i>
                        Draw Signature
                    </button>
                    <button type="button" id="tab-upload" onclick="switchSigTab('upload')"
                            class="flex-1 px-4 py-2.5 text-xs font-semibold text-slate-500 hover:bg-slate-100">
                        <i data-lucide="upload" class="w-3.5 h-3.5 inline"></i>
                        Upload Image
                    </button>
                </div>

                {{-- Draw Pad --}}
                <div id="panel-draw" class="p-5">
                    <p class="text-xs text-slate-500 mb-3">Sign in the box below using your mouse, trackpad, or touchscreen.</p>
                    <div class="border-2 border-dashed border-slate-300 rounded-xl bg-white">
                        <canvas id="sigCanvas" class="w-full cursor-crosshair touch-none" style="height: 180px;"></canvas>
                    </div>
                    <div class="flex items-center justify-between mt-3 flex-wrap gap-2">
                        <button type="button" onclick="clearCanvas()"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50">
                            <i data-lucide="eraser" class="w-3.5 h-3.5"></i>
                            Clear
                        </button>
                        <form method="POST" action="{{ route('profile.signature.draw') }}" id="drawForm">
                            @csrf
                            <input type="hidden" name="signature_data" id="signatureData">
                            <button type="button" onclick="saveCanvas()"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white text-xs font-semibold rounded-lg">
                                <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                Save Signature
                            </button>
                        </form>
                    </div>
                    @error('signature_data')
                        <p class="text-xs text-rose-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Upload --}}
                <div id="panel-upload" class="p-5 hidden">
                    <p class="text-xs text-slate-500 mb-3">Upload a clean scan or photo of your signature on a white background. PNG or JPG, max 1 MB.</p>
                    <form method="POST" action="{{ route('profile.signature.upload') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" name="signature" required accept=".png,.jpg,.jpeg"
                               class="w-full text-xs file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-ua-red-50 file:text-ua-red-700 hover:file:bg-ua-red-100">
                        @error('signature')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white text-xs font-semibold rounded-lg">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                            Upload Signature
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Change password ── --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center gap-2">
            <i data-lucide="lock" class="w-4 h-4 text-slate-500"></i>
            <h3 class="font-semibold text-slate-900">Change Password</h3>
        </div>
        <form method="POST" action="{{ route('profile.password') }}" class="p-5 sm:p-6 space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Current Password</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="w-full sm:max-w-xs px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                @error('current_password')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">New Password</label>
                <input type="password" name="password" required autocomplete="new-password"
                       class="w-full sm:max-w-xs px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Confirm New Password</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full sm:max-w-xs px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                @error('password')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-sm font-semibold shadow-sm">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    Update Password
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    document.getElementById('avatarInput')?.addEventListener('change', function () {
        if (this.files.length) {
            document.getElementById('avatarForm').submit();
        }
    });

    // ── Signature tab switching ───────────────────────────────────────
    function switchSigTab(tab) {
        const drawBtn = document.getElementById('tab-draw');
        const uploadBtn = document.getElementById('tab-upload');
        const drawPanel = document.getElementById('panel-draw');
        const uploadPanel = document.getElementById('panel-upload');

        if (tab === 'draw') {
            drawBtn.classList.add('text-ua-red-700', 'bg-white', 'border-b-2', 'border-ua-red-600');
            drawBtn.classList.remove('text-slate-500', 'hover:bg-slate-100');
            uploadBtn.classList.remove('text-ua-red-700', 'bg-white', 'border-b-2', 'border-ua-red-600');
            uploadBtn.classList.add('text-slate-500', 'hover:bg-slate-100');
            drawPanel.classList.remove('hidden');
            uploadPanel.classList.add('hidden');
        } else {
            uploadBtn.classList.add('text-ua-red-700', 'bg-white', 'border-b-2', 'border-ua-red-600');
            uploadBtn.classList.remove('text-slate-500', 'hover:bg-slate-100');
            drawBtn.classList.remove('text-ua-red-700', 'bg-white', 'border-b-2', 'border-ua-red-600');
            drawBtn.classList.add('text-slate-500', 'hover:bg-slate-100');
            uploadPanel.classList.remove('hidden');
            drawPanel.classList.add('hidden');
        }
    }

    // ── Signature canvas drawing ──────────────────────────────────────
    (function () {
        const canvas = document.getElementById('sigCanvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        let drawing = false, hasDrawn = false, lastX = 0, lastY = 0;

        function resizeCanvas() {
            const ratio = window.devicePixelRatio || 1;
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            ctx.scale(ratio, ratio);
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#1e293b';
            ctx.lineWidth = 2.5;
        }
        resizeCanvas();
        window.addEventListener('resize', () => {
            const data = canvas.toDataURL();
            resizeCanvas();
            const img = new Image();
            img.onload = () => ctx.drawImage(img, 0, 0, canvas.getBoundingClientRect().width, canvas.getBoundingClientRect().height);
            img.src = data;
        });

        function getCoords(e) {
            const rect = canvas.getBoundingClientRect();
            if (e.touches && e.touches.length > 0) {
                return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
            }
            return { x: e.clientX - rect.left, y: e.clientY - rect.top };
        }

        function start(e) {
            e.preventDefault();
            drawing = true;
            const { x, y } = getCoords(e);
            lastX = x; lastY = y;
        }
        function move(e) {
            if (!drawing) return;
            e.preventDefault();
            const { x, y } = getCoords(e);
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();
            lastX = x; lastY = y;
            hasDrawn = true;
        }
        function end() { drawing = false; }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end);

        window.clearCanvas = function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasDrawn = false;
        };

        window.saveCanvas = function () {
            if (!hasDrawn) {
                alert('Please draw your signature before saving.');
                return;
            }
            const dataUrl = canvas.toDataURL('image/png');
            document.getElementById('signatureData').value = dataUrl;
            document.getElementById('drawForm').submit();
        };
    })();
</script>
@endsection
