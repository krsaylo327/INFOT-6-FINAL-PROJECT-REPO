<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Signature;
use App\Notifications\RequestDecided;
use App\Services\ApprovalChainService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalChainService $chain,
    ) {}

    private function captureSignature(Approval $approval, $user, string $ip, string $decision, ?string $remarks): void
    {
        if (!$user->hasSignature()) {
            return;
        }

        $tr = $approval->travelRequest;
        Signature::create([
            'signable_type'            => Approval::class,
            'signable_id'              => $approval->id,
            'signer_id'                => $user->id,
            'purpose'                  => 'travel_approval',
            'signature_image_path'     => $user->signature_path,
            'document_hash'            => Signature::computeDocumentHash([
                'approval_id'  => $approval->id,
                'request_no'   => $tr->request_no,
                'traveler'     => $tr->user->name,
                'destination'  => $tr->destination,
                'date_from'    => $tr->date_from->toDateString(),
                'date_to'      => $tr->date_to->toDateString(),
                'level'        => $approval->level,
                'decision'     => $decision,
                'acted_at'     => now()->toIso8601String(),
            ]),
            'verification_code'        => Signature::generateVerificationCode(),
            'signer_name_snapshot'     => $user->name,
            'signer_position_snapshot' => $user->requested_position,
            'ip_address'               => $ip,
            'decision_remarks'         => $remarks,
            'decision'                 => $decision,
            'signed_at'                => now(),
        ]);
    }

    public function index()
    {
        $user = auth()->user();

        if (!in_array($user->role, ['approver', 'admin'])) {
            abort(403);
        }

        // Only show pending approvals that are the *current* level for their travel request
        // (i.e. no earlier-level approval is still pending on the same request).
        $approvals = Approval::with(['travelRequest.user', 'travelRequest.department'])
            ->from('approvals as a')
            ->where('a.approver_id', $user->id)
            ->where('a.action', 'pending')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('approvals as a2')
                  ->whereColumn('a2.travel_request_id', 'a.travel_request_id')
                  ->where('a2.action', 'pending')
                  ->whereColumn('a2.level', '<', 'a.level');
            })
            ->select('a.*')
            ->latest('a.created_at')
            ->get();

        return view('approvals.index', compact('user', 'approvals'));
    }

    public function update(Request $request, Approval $approval)
    {
        $user = auth()->user();

        if ($approval->approver_id !== $user->id && $user->role !== 'admin') {
            abort(403);
        }

        if ($approval->action !== 'pending') {
            return redirect()
                ->route('approvals.index')
                ->with('error', 'This approval has already been acted on.');
        }

        // Guard: ensure no earlier-level approval is still pending.
        $earlierPending = $approval->travelRequest
            ->approvals()
            ->where('level', '<', $approval->level)
            ->where('action', 'pending')
            ->exists();

        if ($earlierPending) {
            return redirect()
                ->route('approvals.index')
                ->with('error', 'A lower-level approval is still pending. Please wait for your turn.');
        }

        $validated = $request->validate([
            'action'       => ['required', 'in:approved,rejected,noted'],
            'remarks'      => ['nullable', 'string'],
            'security_key' => ['required', 'string'],
        ]);

        if (!Hash::check($validated['security_key'], $user->password)) {
            return back()
                ->withErrors(['security_key' => 'Incorrect security key. Please enter your account password.'])
                ->withInput();
        }

        $approval->update([
            'action'   => $validated['action'],
            'remarks'  => $validated['remarks'] ?? null,
            'acted_at' => now(),
        ]);

        $this->captureSignature($approval, $user, $request->ip(), $validated['action'], $validated['remarks'] ?? null);

        $travelRequest = $approval->travelRequest;

        if ($validated['action'] === 'rejected') {
            $this->chain->reject($travelRequest);
            $travelRequest->user->notify(new RequestDecided($travelRequest, 'rejected'));

            AuditLogger::log('approval.rejected', $travelRequest, [
                'level'       => $approval->level,
                'approver_id' => $user->id,
                'remarks'     => $validated['remarks'] ?? null,
            ]);

            return redirect()
                ->route('approvals.index')
                ->with('success', 'Request has been rejected.');
        }

        if ($validated['action'] === 'noted') {
            // Research Director noting step — advance chain without full approval
            $next = $this->chain->advance($travelRequest, $approval);

            AuditLogger::log('approval.noted', $travelRequest, [
                'level'      => $approval->level,
                'approver_id' => $user->id,
                'remarks'    => $validated['remarks'] ?? null,
                'next_level' => $next?->level,
            ]);

            $msg = $next
                ? "Request noted and forwarded to the next approver."
                : 'Request noted and fully approved.';

            return redirect()
                ->route('approvals.index')
                ->with('success', $msg);
        }

        // Approved — advance the chain
        $next = $this->chain->advance($travelRequest, $approval);

        AuditLogger::log('approval.approved', $travelRequest, [
            'level'          => $approval->level,
            'approver_id'    => $user->id,
            'remarks'        => $validated['remarks'] ?? null,
            'next_level'     => $next?->level,
            'fully_approved' => $next === null,
        ]);

        $msg = $next
            ? "Level {$approval->level} approved. Routed to Level {$next->level}."
            : 'Request fully approved.';

        return redirect()
            ->route('approvals.index')
            ->with('success', $msg);
    }
}
