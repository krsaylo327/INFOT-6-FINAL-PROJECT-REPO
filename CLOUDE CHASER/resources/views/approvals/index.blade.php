@extends('layouts.app')

@section('title', 'Pending Approvals')
@section('eyebrow', 'Review')
@section('page_title', 'Pending Approvals')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Pending Approvals</h2>
        <p class="text-sm text-slate-500">Review and take action on travel requests awaiting your decision.</p>
    </div>

    @if(!$user->hasSignature())
    <div class="mb-5 flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl">
        <i data-lucide="pen-tool" class="w-5 h-5 text-amber-600 mt-0.5 shrink-0"></i>
        <div class="flex-1">
            <p class="text-sm font-semibold text-amber-800">No digital signature on file</p>
            <p class="text-xs text-amber-700 mt-0.5">
                Your approvals will be recorded, but no digital signature will be embedded.
                <a href="{{ route('profile.show') }}#signature" class="underline font-medium">Upload or draw your signature</a>
                to add a verifiable digital signature to every decision.
            </p>
        </div>
    </div>
    @endif

    @forelse($approvals as $approval)
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 mb-4 hover:shadow-sm">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-mono text-xs text-slate-500">{{ $approval->travelRequest->request_no }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold">
                            Level {{ $approval->level }}
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-ua-red-600"></i>
                        {{ $approval->travelRequest->destination }}
                    </h3>
                </div>
                <a href="{{ route('travel-requests.show', $approval->travelRequest) }}"
                   class="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    Full details
                </a>
            </div>

            <div class="grid sm:grid-cols-3 gap-4 text-sm mb-5">
                <div>
                    <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Traveler</p>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-ua-red-500 to-ua-red-700 flex items-center justify-center text-white text-xs font-semibold">
                            {{ strtoupper(substr($approval->travelRequest->user->name, 0, 1)) }}
                        </div>
                        <span class="font-medium">{{ $approval->travelRequest->user->name }}</span>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Department</p>
                    <p class="font-medium">{{ $approval->travelRequest->department->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Dates</p>
                    <p class="font-medium">
                        {{ $approval->travelRequest->date_from->format('M d') }} – {{ $approval->travelRequest->date_to->format('M d, Y') }}
                    </p>
                </div>
            </div>

            <div class="mb-5">
                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Purpose</p>
                <p class="text-sm text-slate-700 leading-relaxed">{{ $approval->travelRequest->purpose }}</p>
            </div>

            <form method="POST" action="{{ route('approvals.update', $approval) }}" class="border-t border-slate-100 pt-5">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Remarks (optional)</label>
                    <textarea name="remarks" rows="2"
                              placeholder="Add a note for the traveler..."
                              class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">{{ old('remarks') }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                        <i data-lucide="key-round" class="inline w-3.5 h-3.5 mr-1 text-amber-500"></i>
                        Security Key <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" name="security_key" required
                           placeholder="Enter your account password to confirm"
                           class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400 @error('security_key') border-rose-400 @enderror">
                    @error('security_key')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-slate-400 flex items-center gap-1">
                        <i data-lucide="info" class="w-3 h-3"></i>
                        This is your login password — required to confirm your identity before submitting a decision.
                    </p>
                </div>

                @if($approval->is_noter)
                {{-- Research Director noting step --}}
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2 text-xs text-slate-500 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 mr-2">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-amber-600"></i>
                        As Research Director, you <strong>note</strong> this request to acknowledge and forward it. Approval is handled by the VP for Research.
                    </div>
                    <button type="submit" name="action" value="noted"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-semibold">
                        <i data-lucide="pencil-line" class="w-4 h-4"></i>
                        Note & Forward
                    </button>
                </div>
                @else
                {{-- Standard approver --}}
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" name="action" value="approved"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        Approve
                    </button>
                    <button type="submit" name="action" value="rejected"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        Reject
                    </button>
                </div>
                @endif
            </form>
        </div>
    @empty
        <div class="bg-white rounded-2xl border border-slate-200 text-center py-16 px-6">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="check-check" class="w-8 h-8 text-emerald-500"></i>
            </div>
            <h3 class="font-semibold text-slate-900 mb-1">You're all caught up! 🎉</h3>
            <p class="text-sm text-slate-500">No pending approvals at the moment.</p>
        </div>
    @endforelse
@endsection
