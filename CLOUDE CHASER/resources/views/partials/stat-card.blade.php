@php
    $color     = $color ?? 'slate';
    $highlight = $highlight ?? false;
    $href      = $href ?? null;

    $colorMap = [
        'slate'   => ['bg' => 'bg-slate-100',   'text' => 'text-slate-600'],
        'amber'   => ['bg' => 'bg-amber-100',   'text' => 'text-amber-600'],
        'emerald' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600'],
        'rose'    => ['bg' => 'bg-rose-100',    'text' => 'text-rose-600'],
        'indigo'  => ['bg' => 'bg-indigo-100',  'text' => 'text-indigo-600'],
        'ua-red'  => ['bg' => 'bg-ua-red-100',  'text' => 'text-ua-red-700'],
    ];
    $c = $colorMap[$color] ?? $colorMap['slate'];

    $tag   = $href ? 'a' : 'div';
    $attrs = $href ? "href=\"{$href}\"" : '';
@endphp

<{{ $tag }} {{ $attrs }} class="relative p-4 sm:p-5 bg-white rounded-2xl border {{ $highlight ? 'border-ua-red-200 ring-1 ring-ua-red-100' : 'border-slate-200' }} {{ $href ? 'hover:shadow-md hover:border-slate-300 transition-shadow cursor-pointer' : 'hover:shadow-sm' }} block">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-medium text-slate-500 truncate">{{ $label }}</p>
            <p class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1">{{ $value }}</p>
        </div>
        <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} flex items-center justify-center shrink-0">
            <i data-lucide="{{ $icon }}" class="w-5 h-5 {{ $c['text'] }}"></i>
        </div>
    </div>
    @if($href)
        <p class="text-[10px] text-slate-400 mt-2 font-medium">View all →</p>
    @endif
</{{ $tag }}>
