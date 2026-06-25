@extends('layouts.app')

@section('title', 'Travel Orders')
@section('eyebrow', "President's Office")
@section('page_title', 'Approve Travel Orders')

@section('content')
<div class="max-w-6xl mx-auto space-y-8">

    {{-- PENDING SIGNATURE — numbered by Records, awaiting President's signature --}}
    <div>
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-base font-semibold">Awaiting Your Signature</h2>
            @if($submitted->count())
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                    {{ $submitted->count() }}
                </span>
            @endif
            <p class="text-xs text-slate-500 ml-auto">These Travel Orders have been auto-generated and are ready for your signature.</p>
        </div>

        @if($submitted->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-400 mx-auto mb-2"></i>
                <p class="text-sm text-slate-500">No pending Travel Orders</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left">
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">TO Number</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Traveler</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Dean / Department</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Event</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Type</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Dates</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($submitted as $to)
                        @php $toTravelers = $to->travelers->count() ? $to->travelers : collect([$to->traveler])->filter(); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-ua-red-700 whitespace-nowrap">{{ $to->to_number }}</td>
                            <td class="px-4 py-3">
                                @foreach($toTravelers as $t)
                                <p class="font-medium text-slate-800 {{ !$loop->first ? 'mt-0.5' : '' }}">{{ $t->name }}</p>
                                @endforeach
                                @if($toTravelers->count() > 1)
                                <p class="text-xs text-indigo-600 font-medium mt-0.5">{{ $toTravelers->count() }} travelers</p>
                                @else
                                <p class="text-xs text-slate-400">{{ $toTravelers->first()?->requested_position ?? '—' }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-slate-700">{{ $to->dean->name }}</p>
                                <p class="text-xs text-slate-400">{{ $to->department->abbreviation ?? $to->department->name }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800 truncate max-w-[200px]">{{ $to->event_name }}</p>
                                <p class="text-xs text-slate-400">{{ $to->destination }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                    {{ $to->type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ ucfirst($to->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                {{ $to->date_from->format('M j') }} – {{ $to->date_to->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 justify-end">
                                    <a href="{{ route('travel-orders.show', $to) }}"
                                       class="text-xs text-slate-600 hover:text-ua-red-600 px-2 py-1 border border-slate-200 rounded-lg">
                                        View
                                    </a>
                                    <a href="{{ route('travel-orders.letter', $to) }}" target="_blank"
                                       class="text-xs text-slate-600 hover:text-ua-red-600 px-2 py-1 border border-slate-200 rounded-lg">
                                        Letter
                                    </a>
                                    <button type="button"
                                            onclick="openIssueModal({{ $to->id }}, '{{ addslashes($to->event_name) }}')"
                                            class="text-xs bg-ua-red-600 hover:bg-ua-red-700 text-white px-3 py-1.5 rounded-lg font-medium">
                                        Sign TO
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

    {{-- PENDING RELEASE — signed by President, waiting for Records Office to release --}}
    <div>
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-base font-semibold">Signed — Awaiting Records Release</h2>
            @if($pendingRelease->count())
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                    {{ $pendingRelease->count() }}
                </span>
            @endif
        </div>

        @if($pendingRelease->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-6 text-center">
                <p class="text-sm text-slate-400">No Travel Orders are currently waiting for Records Office release.</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left">
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Traveler</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Event</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Dates</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Approved</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($pendingRelease as $to)
                        @php $toTravelers = $to->travelers->count() ? $to->travelers : collect([$to->traveler])->filter(); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                @foreach($toTravelers as $t)
                                <p class="font-medium text-slate-800{{ !$loop->first ? ' mt-0.5' : '' }}">{{ $t->name }}</p>
                                @endforeach
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800 truncate max-w-[200px]">{{ $to->event_name }}</p>
                                <p class="text-xs text-slate-400">{{ $to->destination }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                {{ $to->date_from->format('M j') }} – {{ $to->date_to->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-400">
                                {{ $to->issued_at?->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                                    Pending Release
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ISSUED --}}
    <div>
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-base font-semibold">Issued Travel Orders</h2>
            @if($issued->count())
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                    {{ $issued->count() }}
                </span>
            @endif
        </div>

        @if($issued->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <i data-lucide="file-text" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                <p class="text-sm text-slate-500">No issued Travel Orders yet</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left">
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">TO Number</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Traveler</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Event / Destination</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Type</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Dates</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wide">Issued</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($issued as $to)
                        @php $toTravelers = $to->travelers->count() ? $to->travelers : collect([$to->traveler])->filter(); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $to->to_number }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @foreach($toTravelers as $t)
                                <p class="font-medium text-slate-800 {{ !$loop->first ? 'mt-0.5' : '' }}">{{ $t->name }}</p>
                                @endforeach
                                @if($toTravelers->count() > 1)
                                <p class="text-xs text-indigo-600 font-medium mt-0.5">{{ $toTravelers->count() }} travelers</p>
                                @else
                                <p class="text-xs text-slate-400">{{ $to->department->abbreviation ?? '—' }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800 truncate max-w-[180px]">{{ $to->event_name }}</p>
                                <p class="text-xs text-slate-400">{{ $to->destination }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                    {{ $to->type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ ucfirst($to->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                {{ $to->date_from->format('M j') }} – {{ $to->date_to->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-400">
                                {{ $to->issued_at?->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 justify-end">
                                    <a href="{{ route('travel-orders.show', $to) }}"
                                       class="text-xs text-slate-600 hover:text-ua-red-600 px-2 py-1 border border-slate-200 rounded-lg">
                                        View
                                    </a>
                                    <a href="{{ route('travel-orders.print', $to) }}" target="_blank"
                                       class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg font-medium">
                                        Print TO
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

{{-- Approve TO Modal --}}
<div id="issueModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/50 px-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-ua-red-50 flex items-center justify-center">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-ua-red-600"></i>
            </div>
            <div>
                <h3 class="text-base font-semibold text-slate-900">Sign Travel Order</h3>
                <p id="issueModalEvent" class="text-xs text-slate-500 truncate max-w-xs"></p>
            </div>
        </div>

        <div class="mb-4 flex items-start gap-2 p-3 bg-indigo-50 border border-indigo-200 rounded-xl">
            <i data-lucide="info" class="w-4 h-4 text-indigo-600 mt-0.5 shrink-0"></i>
            <p class="text-xs text-indigo-800 leading-relaxed">
                This Travel Order has already been numbered. Your signature finalizes it and forwards it to the Records Office for physical release.
            </p>
        </div>

        <form id="issueForm" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    <i data-lucide="key-round" class="inline w-3.5 h-3.5 mr-1 text-amber-500"></i>
                    Security Key <span class="text-rose-500">*</span>
                </label>
                <input type="password" name="security_key" id="issueSecurityKey" required
                       placeholder="Enter your account password"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400 @error('security_key') border-rose-400 @enderror">
                @error('security_key')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-xs text-slate-400 flex items-center gap-1">
                    <i data-lucide="info" class="w-3 h-3"></i>
                    Required to confirm your identity. A digital signature will be embedded if you have one on file.
                </p>
            </div>

            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeIssueModal()"
                        class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">
                    Cancel
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white text-sm font-semibold rounded-xl">
                    <i data-lucide="pen-tool" class="w-4 h-4"></i>
                    Sign &amp; Forward to Records
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openIssueModal(toId, eventName) {
        const baseUrl = "{{ url('president/travel-orders') }}";
        document.getElementById('issueForm').action = baseUrl + '/' + toId + '/issue';
        document.getElementById('issueModalEvent').textContent = eventName;
        document.getElementById('issueSecurityKey').value = '';
        document.getElementById('issueModal').classList.remove('hidden');
        setTimeout(() => document.getElementById('issueSecurityKey').focus(), 50);
    }
    function closeIssueModal() {
        document.getElementById('issueModal').classList.add('hidden');
    }
    document.getElementById('issueModal').addEventListener('click', function(e) {
        if (e.target === this) closeIssueModal();
    });
</script>
@endpush
@endsection
