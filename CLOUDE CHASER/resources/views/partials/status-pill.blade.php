@php
    $status = $status ?? 'pending';

    $styles = [
        'pending'  => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'dot' => 'bg-amber-500',   'icon' => 'clock'],
        'approved' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500', 'icon' => 'check'],
        'rejected' => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'dot' => 'bg-rose-500',    'icon' => 'x'],
        'draft'    => ['bg' => 'bg-slate-100',  'text' => 'text-slate-700',   'dot' => 'bg-slate-400',   'icon' => 'edit-3'],
        'assigned' => ['bg' => 'bg-indigo-50',  'text' => 'text-indigo-700',  'dot' => 'bg-indigo-500',  'icon' => 'user-plus'],
        'declined' => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'dot' => 'bg-rose-400',    'icon' => 'x-circle'],
    ];
    $s = $styles[$status] ?? $styles['pending'];
@endphp

<span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-[11px] font-medium {{ $s['bg'] }} {{ $s['text'] }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $s['dot'] }}"></span>
    {{ ucfirst($status) }}
</span>
