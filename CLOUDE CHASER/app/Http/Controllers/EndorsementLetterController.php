<?php

namespace App\Http\Controllers;

use App\Models\EndorsementLetter;
use App\Models\Invitation;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EndorsementLetterController extends Controller
{
    /**
     * Index for VPAA/VPREI — list endorsements awaiting their review.
     * Filtered by category matching approver_type.
     */
    public function index()
    {
        $user = auth()->user();
        $this->authorizeReviewer($user);

        $category = $user->approver_type === 'vp_research' ? 'research' : 'academic';

        $endorsements = EndorsementLetter::with(['invitation', 'dean.department', 'staff'])
            ->where('category', $category)
            ->whereIn('status', ['submitted', 'approved', 'rejected'])
            ->latest('submitted_at')
            ->get();

        return view('endorsement_letters.index', compact('user', 'endorsements'));
    }

    /**
     * Dean's own endorsement letters list.
     */
    public function myIndex()
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        $endorsements = EndorsementLetter::with(['invitation', 'staff', 'reviewer'])
            ->where('dean_id', $user->id)
            ->latest()
            ->get();

        return view('endorsement_letters.my_index', compact('user', 'endorsements'));
    }

    /**
     * A traveler/staff member's list of endorsements they've been named on.
     */
    public function staffIndex()
    {
        $user = auth()->user();

        $endorsements = EndorsementLetter::with(['invitation', 'dean', 'reviewer', 'travelOrder'])
            ->whereHas('staff', fn ($q) => $q->where('users.id', $user->id))
            ->latest()
            ->get();

        return view('endorsement_letters.staff_index', compact('user', 'endorsements'));
    }

    /**
     * Form to create an endorsement letter for an invitation.
     */
    public function create(Invitation $invitation)
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        if ($invitation->assigned_to !== $user->id) {
            abort(403, 'You can only endorse for invitations assigned to you.');
        }

        if (!$invitation->canRespond()) {
            return redirect()->route('invitations.show', $invitation)
                ->with('error', 'This invitation has already been responded to.');
        }

        // Eligible staff: same department, non-dean, active users (excluding the dean themselves)
        $staff = User::where('department_id', $user->department_id)
            ->where('id', '!=', $user->id)
            ->where('status', 'active')
            ->whereIn('role', ['traveler', 'approver'])
            ->orderBy('name')
            ->get();

        return view('endorsement_letters.create', compact('user', 'invitation', 'staff'));
    }

    /**
     * Store new endorsement letter (as draft or submitted).
     */
    public function store(Request $request, Invitation $invitation)
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        if ($invitation->assigned_to !== $user->id) {
            abort(403);
        }

        if (!$invitation->canRespond()) {
            return back()->with('error', 'This invitation has already been responded to.');
        }

        $validated = $request->validate([
            'reason_for_endorsing' => ['required', 'string', 'min:10', 'max:1000'],
            'justification'        => ['required', 'string', 'min:10', 'max:1000'],
            'expected_outcomes'    => ['required', 'string', 'min:10', 'max:1000'],
            'staff'                => ['required', 'array', 'min:1'],
            'staff.*.user_id'      => ['required', 'exists:users,id'],
            'staff.*.position'     => ['nullable', 'string', 'max:100'],
            'staff.*.role_in_event'=> ['nullable', 'string', 'max:100'],
            'action'               => ['required', Rule::in(['draft', 'submit'])],
        ]);

        $endorsement = EndorsementLetter::create([
            'invitation_id'        => $invitation->id,
            'dean_id'              => $user->id,
            'category'             => $invitation->type,
            'reason_for_endorsing' => $validated['reason_for_endorsing'],
            'justification'        => $validated['justification'],
            'expected_outcomes'    => $validated['expected_outcomes'],
            'estimated_cost'       => 0,
            'status'               => $validated['action'] === 'submit' ? 'submitted' : 'draft',
            'submitted_at'         => $validated['action'] === 'submit' ? now() : null,
        ]);

        // Attach staff
        $staffData = [];
        foreach ($validated['staff'] as $staffEntry) {
            $staffData[$staffEntry['user_id']] = [
                'position'      => $staffEntry['position'] ?? null,
                'role_in_event' => $staffEntry['role_in_event'] ?? null,
                'notified_at'   => now(),
            ];
        }
        $endorsement->staff()->sync($staffData);

        // Update invitation status if submitted
        if ($validated['action'] === 'submit') {
            $invitation->update(['status' => 'endorsed']);
            $this->notifyStaff($endorsement);
            $this->notifyReviewer($endorsement);
        }

        $message = $validated['action'] === 'submit'
            ? 'Endorsement letter submitted for ' . $endorsement->reviewerLabel() . ' review.'
            : 'Endorsement letter saved as draft.';

        return redirect()->route('endorsement-letters.show', $endorsement)->with('success', $message);
    }

    /**
     * Show an endorsement letter — accessible by dean, reviewer (VPAA/VPREI),
     * president, admin, or endorsed staff members.
     */
    public function show(EndorsementLetter $endorsementLetter)
    {
        $user = auth()->user();
        $this->authorizeView($user, $endorsementLetter);

        $endorsementLetter->load([
            'invitation.attachments',
            'invitation.issuer',
            'dean.department',
            'reviewer',
            'staff',
            'travelOrder',
        ]);

        // Generate a 6-digit PIN for VPAA/VPREI review — required to authorize the decision
        $reviewPin = null;
        $isReviewer = $user->role === 'approver' && in_array($user->approver_type, ['vp_academic', 'vp_research']);
        if ($isReviewer && $endorsementLetter->isSubmitted()) {
            $key = "endorsement_pin_{$endorsementLetter->id}";
            if (!session()->has($key)) {
                session([$key => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT)]);
            }
            $reviewPin = session($key);
        }

        return view('endorsement_letters.show', compact('user', 'endorsementLetter', 'reviewPin'));
    }

    /**
     * Render the endorsement letter as a formal printable letter.
     */
    public function letter(EndorsementLetter $endorsementLetter)
    {
        $user = auth()->user();
        $this->authorizeView($user, $endorsementLetter);

        $endorsementLetter->load([
            'invitation',
            'dean.department',
            'reviewer',
            'staff',
        ]);

        return view('endorsement_letters.letter', compact('endorsementLetter'));
    }

    /**
     * Show edit form (only while draft or rejected).
     */
    public function edit(EndorsementLetter $endorsementLetter)
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        if ($endorsementLetter->dean_id !== $user->id) {
            abort(403);
        }

        if (!$endorsementLetter->isDraft() && !$endorsementLetter->isRejected()) {
            return redirect()->route('endorsement-letters.show', $endorsementLetter)
                ->with('error', 'This endorsement cannot be edited at its current state.');
        }

        $invitation = $endorsementLetter->invitation;
        $staff = User::where('department_id', $user->department_id)
            ->where('id', '!=', $user->id)
            ->where('status', 'active')
            ->whereIn('role', ['traveler', 'approver'])
            ->orderBy('name')
            ->get();

        $endorsementLetter->load('staff');

        return view('endorsement_letters.edit', compact('user', 'endorsementLetter', 'invitation', 'staff'));
    }

    /**
     * Update an existing endorsement (revise after rejection or edit draft).
     */
    public function update(Request $request, EndorsementLetter $endorsementLetter)
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        if ($endorsementLetter->dean_id !== $user->id) {
            abort(403);
        }

        if (!$endorsementLetter->isDraft() && !$endorsementLetter->isRejected()) {
            return back()->with('error', 'This endorsement cannot be edited at its current state.');
        }

        $validated = $request->validate([
            'reason_for_endorsing' => ['required', 'string', 'min:10', 'max:1000'],
            'justification'        => ['required', 'string', 'min:10', 'max:1000'],
            'expected_outcomes'    => ['required', 'string', 'min:10', 'max:1000'],
            'staff'                => ['required', 'array', 'min:1'],
            'staff.*.user_id'      => ['required', 'exists:users,id'],
            'staff.*.position'     => ['nullable', 'string', 'max:100'],
            'staff.*.role_in_event'=> ['nullable', 'string', 'max:100'],
            'action'               => ['required', Rule::in(['draft', 'submit'])],
        ]);

        $endorsementLetter->update([
            'reason_for_endorsing' => $validated['reason_for_endorsing'],
            'justification'        => $validated['justification'],
            'expected_outcomes'    => $validated['expected_outcomes'],
            'status'               => $validated['action'] === 'submit' ? 'submitted' : 'draft',
            'submitted_at'         => $validated['action'] === 'submit' ? now() : $endorsementLetter->submitted_at,
            // Clear previous review when resubmitting
            'reviewed_by'          => $validated['action'] === 'submit' ? null : $endorsementLetter->reviewed_by,
            'reviewed_at'          => $validated['action'] === 'submit' ? null : $endorsementLetter->reviewed_at,
            'review_remarks'       => $validated['action'] === 'submit' ? null : $endorsementLetter->review_remarks,
        ]);

        // Re-sync staff
        $staffData = [];
        foreach ($validated['staff'] as $staffEntry) {
            $staffData[$staffEntry['user_id']] = [
                'position'      => $staffEntry['position'] ?? null,
                'role_in_event' => $staffEntry['role_in_event'] ?? null,
                'notified_at'   => now(),
            ];
        }
        $endorsementLetter->staff()->sync($staffData);

        if ($validated['action'] === 'submit') {
            $endorsementLetter->invitation->update(['status' => 'endorsed']);
            $this->notifyStaff($endorsementLetter);
            $this->notifyReviewer($endorsementLetter);
        }

        $message = $validated['action'] === 'submit'
            ? 'Endorsement letter resubmitted for ' . $endorsementLetter->reviewerLabel() . ' review.'
            : 'Endorsement letter draft updated.';

        return redirect()->route('endorsement-letters.show', $endorsementLetter)->with('success', $message);
    }

    /**
     * VPAA/VPREI reviews — approves or rejects.
     */
    public function review(Request $request, EndorsementLetter $endorsementLetter)
    {
        $user = auth()->user();
        $this->authorizeReviewer($user);

        // Confirm reviewer matches category
        $expectedType = $endorsementLetter->category === 'research' ? 'vp_research' : 'vp_academic';
        if ($user->approver_type !== $expectedType) {
            abort(403, 'You are not authorized to review this endorsement category.');
        }

        if (!$endorsementLetter->isSubmitted()) {
            return back()->with('error', 'Only submitted endorsements can be reviewed.');
        }

        // Require digital signature on file before allowing review
        if (!$user->hasSignature()) {
            return back()->withErrors(['signature' => 'You must register a digital signature in your profile before reviewing endorsements.']);
        }

        $validated = $request->validate([
            'decision'    => ['required', Rule::in(['approved', 'rejected'])],
            'remarks'     => ['nullable', 'string', 'max:1000'],
            'review_pin'  => ['required', 'string', 'size:6'],
        ], [
            'review_pin.required' => 'Please enter the 6-digit security PIN shown on screen.',
            'review_pin.size'     => 'The security PIN must be exactly 6 digits.',
        ]);

        // Validate PIN matches the one in session
        $sessionKey = "endorsement_pin_{$endorsementLetter->id}";
        $expectedPin = session($sessionKey);
        if (!$expectedPin || $validated['review_pin'] !== $expectedPin) {
            return back()->withErrors(['review_pin' => 'Incorrect security PIN. Please use the code shown on screen.'])->withInput();
        }

        // If rejecting, require remarks
        if ($validated['decision'] === 'rejected' && empty($validated['remarks'])) {
            return back()->withErrors(['remarks' => 'Please provide a reason for rejection.']);
        }

        // Clear the PIN after successful validation
        session()->forget($sessionKey);

        // ── Create the digital signature record ──────────────────────────────
        // 1. Snapshot the signer's current signature image (copy to immutable path)
        $snapshotPath = "signatures/snapshots/{$endorsementLetter->id}/" . uniqid('sig_', true) . '.png';
        Storage::disk('private')->copy($user->signature_path, $snapshotPath);

        // 2. Compute document fingerprint hash from key fields
        $documentHash = Signature::computeDocumentHash([
            'endorsement_id'    => $endorsementLetter->id,
            'invitation_id'     => $endorsementLetter->invitation_id,
            'dean_id'           => $endorsementLetter->dean_id,
            'category'          => $endorsementLetter->category,
            'estimated_cost'    => (string) $endorsementLetter->estimated_cost,
            'reason'            => $endorsementLetter->reason_for_endorsing,
            'justification'     => $endorsementLetter->justification,
            'staff_ids'         => $endorsementLetter->staff->pluck('id')->sort()->values()->toArray(),
            'decision'          => $validated['decision'],
        ]);

        // 3. Create the signature record
        Signature::create([
            'signer_id'                => $user->id,
            'signable_type'            => EndorsementLetter::class,
            'signable_id'              => $endorsementLetter->id,
            'purpose'                  => 'endorsement_review',
            'signature_image_path'     => $snapshotPath,
            'document_hash'            => $documentHash,
            'verification_code'        => Signature::generateVerificationCode(),
            'signer_name_snapshot'     => $user->name,
            'signer_position_snapshot' => $user->requested_position,
            'ip_address'               => $request->ip(),
            'decision_remarks'         => $validated['remarks'] ?? null,
            'decision'                 => $validated['decision'],
            'signed_at'                => now(),
        ]);

        $endorsementLetter->update([
            'status'         => $validated['decision'],
            'reviewed_by'    => $user->id,
            'reviewed_at'    => now(),
            'review_remarks' => $validated['remarks'] ?? null,
        ]);

        // Reset invitation status if rejected (so dean can revise)
        if ($validated['decision'] === 'rejected') {
            $endorsementLetter->invitation->update(['status' => 'open']);
        }

        // Auto-create Travel Order when approved
        if ($validated['decision'] === 'approved') {
            $this->autoCreateTravelOrder($endorsementLetter);
        }

        // Notify staff and dean about final decision
        $this->notifyDecision($endorsementLetter);

        $action = $validated['decision'] === 'approved' ? 'approved — Travel Order auto-generated' : 'returned for revision';
        return redirect()->route('endorsement-letters.show', $endorsementLetter)
            ->with('success', "Endorsement letter {$action}.");
    }

    /**
     * Auto-create a Travel Order from an approved endorsement letter.
     * The TO is born already numbered, awaiting the President's signature.
     */
    private function autoCreateTravelOrder(EndorsementLetter $endorsement): ?\App\Models\TravelOrder
    {
        // Bail out if a TO already exists for this endorsement (defensive)
        $endorsement->loadMissing(['invitation', 'dean', 'staff']);
        if ($endorsement->travelOrder) {
            return $endorsement->travelOrder;
        }

        $traveler = $endorsement->staff->first();
        if (!$traveler) {
            return null; // no endorsed staff, can't create TO
        }

        $invitation = $endorsement->invitation;
        $dean       = $endorsement->dean;

        // Compose purpose from endorsement justification + expected outcomes
        $purpose = trim(
            ($endorsement->justification ?? '')
            . ($endorsement->expected_outcomes ? "\n\nExpected outcomes: " . $endorsement->expected_outcomes : '')
        );
        if (strlen($purpose) < 20) {
            $purpose = "Attendance to {$invitation->event_name} at {$invitation->venue}, {$invitation->destination}, as endorsed by the "
                . ($endorsement->category === 'research' ? 'Office of the VP for Research, Extension and Innovation' : 'Office of the VP for Academic Affairs') . '.';
        }

        // Generate the official TO number — President's Office sequence within the current year
        $year     = now()->year;
        $sequence = \App\Models\TravelOrder::whereNotNull('to_number')
            ->where('to_number', 'like', "%s. {$year}")
            ->count() + 1;
        $toNumber = 'No.' . str_pad($sequence, 3, '0', STR_PAD_LEFT) . 's. ' . $year;

        $travelOrder = \App\Models\TravelOrder::create([
            'invitation_id'         => $invitation->id,
            'endorsement_letter_id' => $endorsement->id,
            'initiation_type'       => 'official',
            'to_number'             => $toNumber,
            'type'                  => $endorsement->category,
            'receipt_timing'        => 'after_travel',
            'traveler_id'           => $traveler->id,
            'dean_id'               => $dean->id,
            'noted_by'              => $dean->id,
            'department_id'         => $dean->department_id,
            'event_name'            => $invitation->event_name,
            'destination'           => $invitation->destination ?? $invitation->venue,
            'venue'                 => $invitation->venue ?? $invitation->destination,
            'date_from'             => $invitation->date_from,
            'date_to'               => $invitation->date_to,
            'purpose'               => $purpose,
            'status'                => 'pending_signature',
            'budget_code'           => $endorsement->budget_code,
            'grant_account'         => $endorsement->grant_account,
            'grant_title'           => $endorsement->grant_title,
        ]);

        // Attach all endorsed staff as travelers
        $travelOrder->travelers()->sync($endorsement->staff->pluck('id')->toArray());

        return $travelOrder;
    }

    // ---------------- Helpers ----------------

    private function authorizeDean($user): void
    {
        if ($user->role !== 'dean' || ($user->department?->abbreviation === 'PRES')) {
            abort(403, 'Only department deans may create endorsement letters.');
        }
    }

    private function authorizeReviewer($user): void
    {
        if ($user->role !== 'approver' || !in_array($user->approver_type, ['vp_academic', 'vp_research'])) {
            abort(403, 'Only VPAA or VPREI may review endorsement letters.');
        }
    }

    private function authorizeView($user, EndorsementLetter $letter): void
    {
        $isPresident = $user->role === 'dean' && $user->department?->abbreviation === 'PRES';
        $isReviewer  = $user->role === 'approver' && in_array($user->approver_type, ['vp_academic', 'vp_research']);
        $isStaff     = $letter->staff->contains('id', $user->id);

        $allowed = $letter->dean_id === $user->id
            || $isReviewer
            || $isPresident
            || $user->role === 'admin'
            || $isStaff;

        if (!$allowed) {
            abort(403);
        }
    }

    /**
     * Send notification to all endorsed staff (best-effort).
     */
    private function notifyStaff(EndorsementLetter $letter): void
    {
        $letter->loadMissing('staff');
        foreach ($letter->staff as $staffMember) {
            try {
                $staffMember->notify(new \App\Notifications\EndorsementStaffAssigned($letter));
            } catch (\Throwable $e) {
                // Silent fail - notifications shouldn't break the workflow
            }
        }
    }

    /**
     * Send notification to the appropriate VPAA/VPREI for review.
     */
    private function notifyReviewer(EndorsementLetter $letter): void
    {
        $approverType = $letter->category === 'research' ? 'vp_research' : 'vp_academic';

        $reviewer = User::where('role', 'approver')
            ->where('approver_type', $approverType)
            ->where('status', 'active')
            ->first();

        if ($reviewer) {
            try {
                $reviewer->notify(new \App\Notifications\EndorsementSubmittedForReview($letter));
            } catch (\Throwable $e) {
                // Silent fail
            }
        }
    }

    /**
     * Notify dean and staff about the VPAA/VPREI decision.
     */
    private function notifyDecision(EndorsementLetter $letter): void
    {
        try {
            $letter->dean->notify(new \App\Notifications\EndorsementReviewed($letter));
        } catch (\Throwable $e) {
            // Silent fail
        }

        if ($letter->isApproved()) {
            $letter->loadMissing('staff');
            foreach ($letter->staff as $staffMember) {
                try {
                    $staffMember->notify(new \App\Notifications\EndorsementApprovedFinal($letter));
                } catch (\Throwable $e) {
                    // Silent fail
                }
            }
        }
    }
}
