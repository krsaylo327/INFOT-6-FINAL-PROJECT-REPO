@extends('layouts.app')

@section('title', 'Outgoing Documents Register')
@section('eyebrow', 'Records Office')
@section('page_title', 'Outgoing Documents Register')

@section('content')
<div class="max-w-6xl mx-auto space-y-8">

    {{-- QUEUE 1: Pending Physical Release (already signed by President) --}}
    <div>
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-base font-semibold">Pending Physical Release</h2>
            @if($pendingRelease->count())
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                    {{ $pendingRelease->count() }}
                </span>
            @endif
            <p class="text-xs text-slate-500 ml-auto">Signed Travel Orders awaiting release to the traveler.</p>
        </div>

        @if($pendingRelease->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-400 mx-auto mb-2"></i>
                <p class="text-sm text-slate-500">No signed Travel Orders waiting to be released.</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3 font-semibold">TO Number</th>
                            <th class="px-4 py-3 font-semibold">Traveler(s)</th>
                            <th class="px-4 py-3 font-semibold">Dean / Dept</th>
                            <th class="px-4 py-3 font-semibold">Event</th>
                            <th class="px-4 py-3 font-semibold">Signed By</th>
                            <th class="px-4 py-3 font-semibold">Signed On</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($pendingRelease as $to)
                        @php $travelers = $to->travelers->count() ? $to->travelers : collect([$to->traveler])->filter(); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-ua-red-700">{{ $to->to_number }}</td>
                            <td class="px-4 py-3">
                                @foreach($travelers as $t)
                                    <p class="font-medium text-slate-800{{ !$loop->first ? ' mt-0.5' : '' }}">{{ $t->name }}</p>
                                @endforeach
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-slate-700">{{ $to->dean?->name ?? '—' }}</p>
                                <p class="text-xs text-slate-400">{{ $to->department?->abbreviation ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3 truncate max-w-[180px] text-slate-700">{{ $to->event_name }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $to->issuer?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $to->issued_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('travel-orders.print', $to) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-slate-600 border border-slate-200 rounded-lg text-xs font-medium hover:bg-slate-50 hover:text-ua-red-600 hover:border-ua-red-200">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        View TO
                                    </a>
                                    <button onclick="openReleaseModal({{ $to->id }}, '{{ addslashes($to->event_name) }}', '{{ $to->to_number }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-lg text-xs font-semibold shadow-sm">
                                        <i data-lucide="stamp" class="w-3.5 h-3.5"></i>
                                        Release
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- QUEUE 2: Pending Closure (traveler has attested return) --}}
    <div>
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-base font-semibold">Pending Closure</h2>
            @if($pendingClosure->count())
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                    {{ $pendingClosure->count() }}
                </span>
            @endif
            <p class="text-xs text-slate-500 ml-auto">Travelers have submitted return attestations awaiting your closure.</p>
        </div>

        @if($pendingClosure->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-400 mx-auto mb-2"></i>
                <p class="text-sm text-slate-500">No Travel Orders awaiting closure.</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3 font-semibold">TO Number</th>
                            <th class="px-4 py-3 font-semibold">Traveler(s)</th>
                            <th class="px-4 py-3 font-semibold">Event</th>
                            <th class="px-4 py-3 font-semibold">Attested By</th>
                            <th class="px-4 py-3 font-semibold">Attested On</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($pendingClosure as $to)
                        @php $travelers = $to->travelers->count() ? $to->travelers : collect([$to->traveler])->filter(); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-ua-red-700">{{ $to->to_number }}</td>
                            <td class="px-4 py-3">
                                @foreach($travelers as $t)
                                    <p class="font-medium text-slate-800{{ !$loop->first ? ' mt-0.5' : '' }}">{{ $t->name }}</p>
                                @endforeach
                            </td>
                            <td class="px-4 py-3 truncate max-w-[200px] text-slate-700">{{ $to->event_name }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $to->returner?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $to->returned_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('travel-orders.show', $to) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm">
                                    <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                                    Review & Close
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- RECENTLY RELEASED --}}
    <div>
        <h2 class="text-base font-semibold mb-4">Recently Released</h2>
        @if($released->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-6 text-center">
                <p class="text-sm text-slate-400">No Travel Orders have been released yet.</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3 font-semibold">TO Number</th>
                            <th class="px-4 py-3 font-semibold">Traveler(s)</th>
                            <th class="px-4 py-3 font-semibold">Event</th>
                            <th class="px-4 py-3 font-semibold">Dates</th>
                            <th class="px-4 py-3 font-semibold">Released By</th>
                            <th class="px-4 py-3 font-semibold">Released On</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($released as $to)
                        @php $travelers = $to->travelers->count() ? $to->travelers : collect([$to->traveler])->filter(); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-ua-red-700">{{ $to->to_number }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $travelers->first()?->name ?? '—' }}</p>
                                @if($travelers->count() > 1)
                                    <p class="text-xs text-slate-400">+{{ $travelers->count() - 1 }} more</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 truncate max-w-[160px] text-slate-700">{{ $to->event_name }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                {{ $to->date_from->format('M j') }} – {{ $to->date_to->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $to->recordsOfficer?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $to->records_released_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                    {{ $to->status === 'issued' ? 'bg-emerald-100 text-emerald-700' : ($to->status === 'completed' ? 'bg-slate-100 text-slate-700' : ($to->status === 'returned' ? 'bg-blue-100 text-blue-700' : 'bg-indigo-100 text-indigo-700')) }}">
                                    {{ ucfirst($to->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Release Modal --}}
<div id="releaseModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-ua-red-50 flex items-center justify-center">
                    <i data-lucide="stamp" class="w-5 h-5 text-ua-red-600"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">Release Travel Order</p>
                    <p id="releaseEventName" class="text-xs text-slate-500 truncate max-w-[300px]"></p>
                    <p id="releaseToNumber" class="text-xs font-mono text-ua-red-700 mt-0.5"></p>
                </div>
            </div>
        </div>
        <form id="releaseForm" method="POST">
            @csrf
            <div class="px-6 py-5 space-y-4">
                {{-- Pre-release verification checklist --}}
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <p class="text-xs font-semibold text-slate-600 mb-2">Before releasing, confirm:</p>
                    <ul class="space-y-1.5 text-xs text-slate-600">
                        <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500 mt-0.5 shrink-0"></i> Official TO number is assigned and correct</li>
                        <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500 mt-0.5 shrink-0"></i> President's signature is present on the document</li>
                        <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500 mt-0.5 shrink-0"></i> Traveler name, event, and dates match the request</li>
                        <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500 mt-0.5 shrink-0"></i> QR verification code is printed</li>
                    </ul>
                    <p class="text-[11px] text-slate-400 mt-2">Use <strong>View TO</strong> to inspect the document before releasing.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Received By <span class="text-slate-400 font-normal">(who picked up the document)</span></label>
                    <input type="text" name="received_by_name"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200"
                           placeholder="e.g. Atty. Renato M. Alvarez / authorized representative">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Remarks (optional)</label>
                    <textarea name="records_remarks" rows="2"
                              class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200 resize-none"
                              placeholder="Any notes for the outgoing register…"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeReleaseModal()"
                        class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit"
                        class="flex items-center gap-2 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-xl text-sm font-semibold shadow-sm">
                    <i data-lucide="stamp" class="w-4 h-4"></i>
                    Stamp &amp; Release
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openReleaseModal(toId, eventName, toNumber) {
    document.getElementById('releaseEventName').textContent = eventName;
    document.getElementById('releaseToNumber').textContent = toNumber || '';
    document.getElementById('releaseForm').action = '/records-office/travel-orders/' + toId + '/release';
    document.getElementById('releaseModal').classList.remove('hidden');
}
function closeReleaseModal() {
    document.getElementById('releaseModal').classList.add('hidden');
}
document.getElementById('releaseModal').addEventListener('click', function(e) {
    if (e.target === this) closeReleaseModal();
});
</script>
@endpush
@endsection
