@php
    /** @var \Illuminate\Support\Collection|\App\Models\AuditLog[] $logs */
    $logs = $logs ?? collect();

    $iconMap = [
        'request.submitted'          => ['lucide' => 'send',        'tone' => 'sky'],
        'assignment.created'         => ['lucide' => 'user-plus',   'tone' => 'indigo'],
        'assignment.acknowledged'    => ['lucide' => 'check-circle','tone' => 'emerald'],
        'assignment.declined'        => ['lucide' => 'x-circle',    'tone' => 'rose'],
        'approval.approved'          => ['lucide' => 'check',       'tone' => 'emerald'],
        'approval.rejected'          => ['lucide' => 'x',           'tone' => 'rose'],
        'approval.escalated'         => ['lucide' => 'alert-triangle','tone' => 'amber'],
    ];

    $toneClasses = [
        'sky'     => 'bg-sky-100 text-sky-700',
        'indigo'  => 'bg-indigo-100 text-indigo-700',
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'rose'    => 'bg-rose-100 text-rose-700',
        'amber'   => 'bg-amber-100 text-amber-700',
        'slate'   => 'bg-slate-100 text-slate-700',
    ];
@endphp

<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center gap-2">
        <i data-lucide="scroll-text" class="w-4 h-4 text-slate-500"></i>
        <h3 class="font-semibold text-slate-900">Audit Log</h3>
        <span class="ml-auto text-xs text-slate-400">{{ $logs->count() }} entries</span>
    </div>

    @if($logs->isEmpty())
        <div class="p-8 text-center text-sm text-slate-500">No audit entries recorded yet.</div>
    @else
        <ol class="p-5 space-y-4">
            @foreach($logs as $log)
                @php
                    $meta = $iconMap[$log->action] ?? ['lucide' => 'activity', 'tone' => 'slate'];
                    $tone = $toneClasses[$meta['tone']] ?? $toneClasses['slate'];
                @endphp
                <li class="flex items-start gap-3">
                    <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $tone }}">
                        <i data-lucide="{{ $meta['lucide'] }}" class="w-4 h-4"></i>
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ ucwords(str_replace(['.', '_'], ' ', $log->action)) }}
                            </p>
                            <span class="text-xs text-slate-400 whitespace-nowrap">
                                {{ $log->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $log->user->name ?? 'System' }}
                            @if($log->created_at)
                                · {{ $log->created_at->format('M d, Y h:i A') }}
                            @endif
                        </p>
                        @if(!empty($log->metadata))
                            <div class="mt-2 p-2.5 rounded-lg bg-slate-50 border border-slate-100 text-xs text-slate-600 font-mono space-y-0.5">
                                @foreach($log->metadata as $k => $v)
                                    <div>
                                        <span class="text-slate-400">{{ $k }}:</span>
                                        <span>{{ is_array($v) || is_object($v) ? json_encode($v) : (string) $v }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
