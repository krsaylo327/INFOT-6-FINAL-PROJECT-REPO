@extends('layouts.app')

@section('title', 'Invitations')
@section('eyebrow', 'Dean')
@section('page_title', 'Received Invitations')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <h2 class="text-lg font-semibold">Received Invitations</h2>
        <p class="text-sm text-slate-500">Invitations forwarded to you by the President's Office</p>
    </div>

    @if($invitations->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <i data-lucide="inbox" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
            <p class="text-sm font-medium text-slate-500">No invitations yet</p>
            <p class="text-xs text-slate-400 mt-1">The President's Office will forward invitations here when they arrive</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($invitations as $inv)
            @php
                $hasTO = (bool) $inv->travelOrder;
            @endphp

            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden
                @if($inv->isRejected()) opacity-60 @endif">

                {{-- Card header --}}
                <div class="p-5">
                    <div class="flex items-start gap-4">
                        {{-- Icon --}}
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                            {{ $inv->type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                            <i data-lucide="{{ $inv->type === 'academic' ? 'graduation-cap' : 'microscope' }}" class="w-5 h-5"></i>
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-0.5">
                                <p class="font-semibold text-slate-800">{{ $inv->event_name }}</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                    {{ $inv->type === 'academic' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ ucfirst($inv->type) }}
                                </span>

                                {{-- Status badge --}}
                                @if($inv->isRejected())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-700">
                                        <i data-lucide="x-circle" class="w-3 h-3"></i> Declined
                                    </span>
                                @elseif($hasTO)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700">
                                        <i data-lucide="check-circle-2" class="w-3 h-3"></i> TO Created
                                    </span>
                                @elseif($inv->isAccepted())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-sky-100 text-sky-700">
                                        <i data-lucide="check" class="w-3 h-3"></i> Accepted
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700">
                                        <i data-lucide="clock" class="w-3 h-3"></i> Awaiting Response
                                    </span>
                                @endif
                            </div>

                            @if($inv->destination || $inv->venue)
                                <p class="text-sm text-slate-500">
                                    {{ collect([$inv->venue, $inv->destination])->filter()->implode(', ') }}
                                </p>
                            @endif
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $inv->formattedDates() }} &nbsp;·&nbsp; Forwarded {{ $inv->created_at->diffForHumans() }}
                            </p>
                        </div>

                        {{-- Quick action button (top-right) --}}
                        <div class="shrink-0">
                            @if($hasTO)
                                <a href="{{ route('travel-orders.show', $inv->travelOrder) }}"
                                   class="flex items-center gap-1.5 text-xs font-medium text-slate-600 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                    View TO
                                </a>
                            @endif
                            <a href="{{ route('invitations.show', $inv) }}"
                               class="block mt-1 text-xs text-slate-400 hover:text-ua-red-600 text-center">Details</a>
                        </div>
                    </div>

                    {{-- Details snippet --}}
                    @if($inv->details)
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <p class="text-xs text-slate-500 leading-relaxed line-clamp-2">{{ $inv->details }}</p>
                        </div>
                    @endif

                    {{-- Attached files --}}
                    @if($inv->attachments->isNotEmpty())
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-2">
                                Attached Files ({{ $inv->attachments->count() }})
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($inv->attachments as $att)
                                    <a href="{{ route('invitations.attachments.download', [$inv, $att]) }}"
                                       class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600 hover:bg-ua-red-50 hover:border-ua-red-200 hover:text-ua-red-700 transition-colors max-w-[200px]">
                                        <i data-lucide="{{ str_contains($att->mime_type ?? '', 'image') ? 'image' : 'file-text' }}" class="w-3.5 h-3.5 shrink-0"></i>
                                        <span class="truncate">{{ $att->original_name }}</span>
                                        <span class="text-slate-400 shrink-0">{{ $att->formattedSize() }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Action footer --}}
                @php $isPresident = $user->role === 'dean' && $user->department?->abbreviation === 'PRES'; @endphp
                @if($inv->isOpen())
                <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center gap-2 flex-wrap">
                    <p class="text-xs text-slate-500 font-medium mr-1">Respond:</p>

                    {{-- Accept (dean attends personally) --}}
                    <form method="POST" action="{{ route('invitations.accept', $inv) }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            Accept & Attend
                        </button>
                    </form>

                    {{-- Endorse Staff (dean endorses others) — not available to President --}}
                    @if(!$isPresident)
                    <form method="POST" action="{{ route('invitations.endorse', $inv) }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">
                            <i data-lucide="users" class="w-3.5 h-3.5"></i>
                            Endorse Staff
                        </button>
                    </form>
                    @endif

                    {{-- Reject toggle --}}
                    <button type="button"
                            onclick="document.getElementById('reject-{{ $inv->id }}').classList.toggle('hidden')"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        Decline
                    </button>
                </div>

                {{-- Reject form (hidden by default) --}}
                <div id="reject-{{ $inv->id }}" class="hidden px-5 py-4 bg-rose-50 border-t border-rose-100">
                    <form method="POST" action="{{ route('invitations.reject', $inv) }}">
                        @csrf
                        <label class="block text-xs font-semibold text-rose-800 mb-1.5">
                            Reason for declining <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="reject_reason" rows="2" required minlength="5"
                                  placeholder="Briefly explain why this invitation is being declined..."
                                  class="w-full px-3 py-2 text-sm border border-rose-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-rose-300 resize-none"></textarea>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="submit"
                                    class="px-4 py-1.5 text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition-colors">
                                Confirm Decline
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('reject-{{ $inv->id }}').classList.add('hidden')"
                                    class="text-xs text-slate-500 hover:text-slate-700">Cancel</button>
                        </div>
                    </form>
                </div>

                @elseif($inv->isAccepted() && !$hasTO)
                <div class="px-5 py-3.5 bg-sky-50 border-t border-sky-100 flex items-center gap-3 flex-wrap">
                    <p class="text-xs text-sky-700 font-medium flex-1">You accepted this invitation. Create the Travel Order to proceed.</p>
                    <a href="{{ route('travel-orders.create', ['invitation' => $inv->id]) }}"
                       class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-lg transition-colors">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        Create Travel Order
                    </a>

                    {{-- Still allow declining after accepting --}}
                    <button type="button"
                            onclick="document.getElementById('reject-{{ $inv->id }}').classList.toggle('hidden')"
                            class="text-xs text-slate-400 hover:text-rose-600 transition-colors">Decline instead</button>
                </div>

                <div id="reject-{{ $inv->id }}" class="hidden px-5 py-4 bg-rose-50 border-t border-rose-100">
                    <form method="POST" action="{{ route('invitations.reject', $inv) }}">
                        @csrf
                        <label class="block text-xs font-semibold text-rose-800 mb-1.5">
                            Reason for declining <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="reject_reason" rows="2" required minlength="5"
                                  placeholder="Briefly explain why this invitation is being declined..."
                                  class="w-full px-3 py-2 text-sm border border-rose-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-rose-300 resize-none"></textarea>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="submit"
                                    class="px-4 py-1.5 text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition-colors">
                                Confirm Decline
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('reject-{{ $inv->id }}').classList.add('hidden')"
                                    class="text-xs text-slate-500 hover:text-slate-700">Cancel</button>
                        </div>
                    </form>
                </div>

                @elseif($inv->isRejected())
                <div class="px-5 py-3.5 bg-rose-50 border-t border-rose-100">
                    <p class="text-xs text-rose-700 font-medium">Declined</p>
                    @if($inv->reject_reason)
                        <p class="text-xs text-rose-600 mt-0.5">{{ $inv->reject_reason }}</p>
                    @endif
                </div>
                @endif

            </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
