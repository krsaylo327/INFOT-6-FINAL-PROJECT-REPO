@php
    $dot = match($status) {
        'done'     => ['bg-emerald-500', 'text-emerald-600', 'check'],
        'rejected' => ['bg-rose-500',    'text-rose-600',    'x'],
        'waiting'  => ['bg-amber-400',   'text-amber-600',   'clock'],
        default    => ['bg-slate-300',   'text-slate-400',   'minus'],
    };
@endphp

<div class="relative flex gap-4 pb-6 timeline-item">
    {{-- Vertical connector line --}}
    @if(!$last)
        <div class="timeline-line"></div>
    @endif

    {{-- Dot --}}
    <div class="relative z-10 w-10 h-10 rounded-full {{ $dot[0] }} flex items-center justify-center shrink-0 shadow-sm">
        <i data-lucide="{{ $icon }}" class="w-4 h-4 text-white"></i>
    </div>

    {{-- Content --}}
    <div class="flex-1 pt-1.5 min-w-0">
        <p class="text-sm font-semibold text-slate-900">{{ $label }}</p>
        <p class="text-xs {{ $dot[1] }} mt-0.5">{{ $detail }}</p>
    </div>
</div>
