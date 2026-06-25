@extends('layouts.app')

@section('title', 'Incoming Documents Register')
@section('eyebrow', 'Records Office')
@section('page_title', 'Incoming Documents Register')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">Log external invitations and route them to the President's inbox.</p>
        <button onclick="document.getElementById('logModal').classList.remove('hidden')"
                class="flex items-center gap-2 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-xl text-sm font-semibold shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Log New Invitation
        </button>
    </div>

    {{-- All received invitations --}}
    @if($items->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
            <i data-lucide="file-input" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
            <p class="text-sm text-slate-500">No invitations logged yet.</p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3 font-semibold">Sender / Event</th>
                        <th class="px-4 py-3 font-semibold">Received</th>
                        <th class="px-4 py-3 font-semibold">Logged By</th>
                        <th class="px-4 py-3 font-semibold">Routed To</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($items as $inv)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800">{{ $inv->event_name }}</p>
                            <p class="text-xs text-slate-500">{{ $inv->sender_org }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ $inv->received_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-xs text-slate-600">
                            {{ $inv->logger?->name ?? $inv->receiver?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ $inv->receiver?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $inv->statusBadgeClass() }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('received-invitations.show', $inv) }}"
                               class="text-xs font-medium text-ua-red-600 hover:text-ua-red-700">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Log New Invitation Modal --}}
<div id="logModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg my-6">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-ua-red-50 flex items-center justify-center">
                    <i data-lucide="file-input" class="w-5 h-5 text-ua-red-600"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">Log Incoming Invitation</p>
                    <p class="text-xs text-slate-500">Will be routed to the President's inbox</p>
                </div>
            </div>
            <button onclick="document.getElementById('logModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('records-office.incoming.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="px-6 py-5 space-y-4 max-h-[60vh] overflow-y-auto">

                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400">Sender Information</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Organization *</label>
                        <input type="text" name="sender_org" required
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200"
                               placeholder="DOST Region VI">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Contact Person</label>
                        <input type="text" name="sender_name"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                        <input type="email" name="sender_email"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Phone</label>
                        <input type="text" name="sender_phone"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200">
                    </div>
                </div>

                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 pt-2">Event Details</p>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Event Name *</label>
                    <input type="text" name="event_name" required
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Venue</label>
                        <input type="text" name="event_venue"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Destination</label>
                        <input type="text" name="event_destination"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Event Date From</label>
                        <input type="date" name="event_date_from"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Event Date To</label>
                        <input type="date" name="event_date_to"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Category</label>
                        <select name="event_type"
                                class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200">
                            <option value="">— Select —</option>
                            <option value="academic">Academic</option>
                            <option value="research">Research</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Date Received *</label>
                        <input type="date" name="received_at" value="{{ date('Y-m-d') }}" required
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Description / Notes</label>
                    <textarea name="description" rows="2"
                              class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ua-red-200 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Attachments (up to 5, 10MB each)</label>
                    <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-ua-red-50 file:text-ua-red-700 hover:file:bg-ua-red-100">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('logModal').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit"
                        class="flex items-center gap-2 px-4 py-2 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-xl text-sm font-semibold shadow-sm">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Log &amp; Route to President
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('logModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>
@endpush
@endsection
