<?php

namespace App\Http\Controllers;

use App\Models\EndorsementLetter;
use App\Models\Invitation;
use App\Models\Signature;
use App\Models\TravelOrder;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TravelOrderController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // DEAN SIDE
    // ──────────────────────────────────────────────────────────────

    /**
     * Dean's list of travel orders they have created.
     */
    public function index()
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        $travelOrders = TravelOrder::with(['traveler', 'department'])
            ->where('dean_id', $user->id)
            ->latest()
            ->get();

        return view('travel_orders.index', compact('user', 'travelOrders'));
    }

    /**
     * Form to create a new Travel Order, optionally pre-filled from an invitation.
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        $travelers      = $this->scopedTravelers($user)->with('department')->orderBy('name')->get();
        $invitation     = null;
        $travelRequest  = null;

        if ($request->filled('invitation')) {
            $invitation = Invitation::where('id', $request->integer('invitation'))
                ->where('assigned_to', $user->id)
                ->whereDoesntHave('travelOrder')
                ->first();
        }

        if ($request->filled('travel_request')) {
            $travelRequest = TravelRequest::where('id', $request->integer('travel_request'))
                ->where('status', 'approved')
                ->whereDoesntHave('travelOrder')
                ->first();
        }

        return view('travel_orders.create', compact('user', 'travelers', 'invitation', 'travelRequest'));
    }

    /**
     * Save a new Travel Order (draft).
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        $validated = $request->validate([
            'invitation_id'     => ['nullable', 'exists:invitations,id'],
            'travel_request_id' => ['nullable', 'exists:travel_requests,id'],
            'type'              => ['required', 'in:academic,research'],
            'receipt_timing'    => ['required', 'in:before_travel,after_travel'],
            'traveler_ids'    => ['required', 'array', 'min:1'],
            'traveler_ids.*'  => ['exists:users,id'],
            'event_name'      => ['required', 'string', 'max:255'],
            'destination'     => ['required', 'string', 'max:255'],
            'venue'           => ['required', 'string', 'max:255'],
            'date_from'       => ['required', 'date'],
            'date_to'         => ['required', 'date', 'after_or_equal:date_from'],
            'purpose'         => ['required', 'string', 'min:20'],
        ]);

        // When accepting an invitation personally, the dean IS the sole traveler.
        if (!empty($validated['invitation_id'])) {
            $travelerIds = [$user->id];
        } else {
            $travelerIds = $validated['traveler_ids'];

            // Dean scope check — all selected travelers must belong to the dean's department
            if ($user->role === 'dean' && $user->department?->abbreviation !== 'PRES') {
                $outsiders = User::whereIn('id', $travelerIds)
                    ->where('department_id', '!=', $user->department_id)
                    ->exists();
                if ($outsiders) {
                    return back()
                        ->withErrors(['traveler_ids' => 'All travelers must be from your department.'])
                        ->withInput();
                }
            }
        }

        $primaryTraveler = User::findOrFail($travelerIds[0]);

        $isSubmitAction = $request->input('action') === 'submit';

        // On submit, the TO is numbered and forwarded to the President for signature
        // (mirrors the endorsement flow). Drafts stay unnumbered.
        $status   = $isSubmitAction ? 'pending_signature' : 'draft';
        $toNumber = $isSubmitAction ? $this->generateToNumber() : null;

        $travelOrder = TravelOrder::create([
            'invitation_id'     => $validated['invitation_id'] ?? null,
            'travel_request_id' => $validated['travel_request_id'] ?? null,
            'to_number'         => $toNumber,
            'type'              => $validated['type'],
            'receipt_timing'    => $validated['receipt_timing'],
            'traveler_id'   => $primaryTraveler->id,
            'dean_id'       => $user->id,
            'department_id' => $primaryTraveler->department_id ?? $user->department_id,
            'event_name'    => $validated['event_name'],
            'destination'   => $validated['destination'],
            'venue'         => $validated['venue'],
            'date_from'     => $validated['date_from'],
            'date_to'       => $validated['date_to'],
            'purpose'       => $validated['purpose'],
            'status'        => $status,
            'budget_code'   => $validated['budget_code'] ?? null,
            'grant_account' => $validated['grant_account'] ?? null,
            'grant_title'   => $validated['grant_title'] ?? null,
        ]);

        $travelOrder->travelers()->sync($travelerIds);

        $message = $isSubmitAction
            ? "Travel Order {$toNumber} submitted to the President's Office for signature."
            : 'Travel Order saved as draft.';

        return redirect()->route('travel-orders.show', $travelOrder)->with('success', $message);
    }

    /**
     * Generate the next official Travel Order number for the current year.
     * Format: "No.NNNs. YYYY"
     */
    private function generateToNumber(): string
    {
        $year     = now()->year;
        $sequence = TravelOrder::whereNotNull('to_number')
            ->where('to_number', 'like', "%s. {$year}")
            ->count() + 1;

        return 'No.' . str_pad($sequence, 3, '0', STR_PAD_LEFT) . 's. ' . $year;
    }

    // ──────────────────────────────────────────────────────────────
    // TRAVELER SIDE — personal travel requests
    // ──────────────────────────────────────────────────────────────

    /**
     * Traveler's own list of personal TOs they requested.
     */
    public function myIndex()
    {
        $user = auth()->user();

        // All Travel Orders where the user is a traveler — whether they requested it
        // personally OR were endorsed by their dean (primary or secondary traveler).
        $travelOrders = TravelOrder::with(['department', 'noter'])
            ->where(function ($q) use ($user) {
                $q->where('traveler_id', $user->id)
                  ->orWhereHas('travelers', fn ($qq) => $qq->where('users.id', $user->id));
            })
            ->latest()
            ->get();

        return view('travel_orders.my_index', compact('user', 'travelOrders'));
    }

    /**
     * Form for traveler to request a personal Travel Order.
     */
    public function personalCreate()
    {
        $user = auth()->user();

        // Find the dean of the traveler's department to auto-select as noter
        $dean = User::where('role', 'dean')
            ->where('department_id', $user->department_id)
            ->where('status', 'active')
            ->first();

        return view('travel_orders.personal_create', compact('user', 'dean'));
    }

    /**
     * Store a personal Travel Order request.
     */
    public function personalStore(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'receipt_timing' => ['required', 'in:before_travel,after_travel'],
            'event_name'     => ['required', 'string', 'max:255'],
            'destination'    => ['required', 'string', 'max:255'],
            'venue'          => ['required', 'string', 'max:255'],
            'date_from'      => ['required', 'date'],
            'date_to'        => ['required', 'date', 'after_or_equal:date_from'],
            'purpose'        => ['required', 'string', 'min:20'],
            'noted_by'       => ['nullable', 'exists:users,id'],
        ]);

        $status = $request->input('action') === 'submit' ? 'submitted' : 'draft';

        $travelOrder = TravelOrder::create([
            'initiation_type' => 'personal',
            'type'            => 'academic', // personal travel is not VP-routed; type kept for the document
            'receipt_timing'  => $validated['receipt_timing'],
            'traveler_id'     => $user->id,
            'dean_id'         => $validated['noted_by'] ?? null,
            'noted_by'        => $validated['noted_by'] ?? null,
            'department_id'   => $user->department_id,
            'event_name'      => $validated['event_name'],
            'destination'     => $validated['destination'],
            'venue'           => $validated['venue'],
            'date_from'       => $validated['date_from'],
            'date_to'         => $validated['date_to'],
            'purpose'         => $validated['purpose'],
            'status'          => $status,
        ]);

        $message = $status === 'submitted'
            ? "Travel Order request submitted to the President's Office."
            : 'Travel Order request saved as draft.';

        return redirect()->route('travel-orders.show', $travelOrder)->with('success', $message);
    }

    // ──────────────────────────────────────────────────────────────
    // ENDORSED STAFF — create TO from an approved endorsement letter
    // ──────────────────────────────────────────────────────────────

    /**
     * Endorsed staff opens TO form with fields pre-filled from the endorsement.
     */
    // ──────────────────────────────────────────────────────────────
    // SHARED
    // ──────────────────────────────────────────────────────────────

    /**
     * Show a single Travel Order detail.
     */
    public function show(TravelOrder $travelOrder)
    {
        $user = auth()->user();

        if (!$this->canAccessTravelOrder($user, $travelOrder)) {
            abort(403);
        }

        $travelOrder->load(['travelers', 'traveler.department', 'dean.department', 'noter', 'department', 'issuer', 'travelRequest', 'vehicleRequest.requester', 'vehicleRequest.reviewer']);

        return view('travel_orders.show', compact('user', 'travelOrder'));
    }

    /**
     * Submit a draft TO to admin.
     */
    public function submit(TravelOrder $travelOrder)
    {
        $user = auth()->user();

        if ($travelOrder->dean_id !== $user->id) {
            abort(403);
        }

        if (!$travelOrder->isDraft()) {
            return back()->with('error', 'This Travel Order has already been submitted.');
        }

        $travelOrder->update([
            'status'    => 'pending_signature',
            'to_number' => $travelOrder->to_number ?? $this->generateToNumber(),
        ]);

        return redirect()->route('travel-orders.show', $travelOrder)
            ->with('success', "Travel Order {$travelOrder->to_number} submitted to the President's Office for signature.");
    }

    /**
     * Printable endorsement letter (Dean's letterhead).
     */
    public function letter(TravelOrder $travelOrder)
    {
        $user = auth()->user();

        if (!$this->canAccessTravelOrder($user, $travelOrder)) {
            abort(403);
        }

        $travelOrder->load(['travelers', 'traveler.department', 'dean.department', 'department']);

        return view('travel_orders.letter', compact('travelOrder'));
    }

    /**
     * Printable Travel Order (President's Office letterhead) — admin or dean may print once issued.
     */
    public function printTo(TravelOrder $travelOrder)
    {
        $user = auth()->user();

        if (!$this->canAccessTravelOrder($user, $travelOrder)) {
            abort(403);
        }

        // Viewable once the President has signed it (pending_release onward).
        if (in_array($travelOrder->status, ['draft', 'submitted', 'pending_signature'], true)) {
            return back()->with('error', 'The Travel Order has not been signed by the President yet.');
        }

        $travelOrder->load([
            'traveler.department', 'dean.department', 'department', 'issuer',
            'endorsementLetter.reviewer', 'endorsementLetter.signatures',
        ]);

        return view('travel_orders.print', compact('travelOrder'));
    }

    // ──────────────────────────────────────────────────────────────
    // ADMIN SIDE
    // ──────────────────────────────────────────────────────────────

    /**
     * President's list — numbered TOs awaiting signature, plus history.
     */
    public function adminIndex()
    {
        $this->authorizePresident();

        // TOs auto-generated by VP approval, awaiting the President's signature
        $submitted = TravelOrder::with(['travelers', 'traveler', 'dean', 'department'])
            ->where('status', 'pending_signature')
            ->oldest('updated_at')
            ->get();

        $pendingRelease = TravelOrder::with(['travelers', 'traveler', 'dean', 'department', 'issuer'])
            ->where('status', 'pending_release')
            ->oldest('issued_at')
            ->get();

        $issued = TravelOrder::with(['travelers', 'traveler', 'dean', 'department', 'issuer'])
            ->whereIn('status', ['issued', 'active', 'returned', 'completed'])
            ->latest('issued_at')
            ->get();

        return view('admin.travel_orders.index', compact('submitted', 'pendingRelease', 'issued'));
    }

    /**
     * President signs an already-numbered Travel Order.
     * Status: pending_signature → pending_release.
     */
    public function issue(Request $request, TravelOrder $travelOrder)
    {
        $this->authorizePresident();

        if (!$travelOrder->isPendingSignature()) {
            return back()->with('error', 'This Travel Order is not awaiting your signature.');
        }

        if (empty($travelOrder->to_number)) {
            return back()->with('error', 'This Travel Order has no number assigned.');
        }

        $validated = $request->validate([
            'security_key' => ['required', 'string'],
        ]);

        $user = auth()->user();
        if (!Hash::check($validated['security_key'], $user->password)) {
            return back()->withErrors(['security_key' => 'Incorrect security key. Please enter your account password.']);
        }

        // A digital signature must be on file so it can be embedded into the Travel Order
        if (!$user->hasSignature()) {
            return back()->withErrors([
                'security_key' => 'You must set up your digital signature first. Go to My Profile → Digital Signature to draw or upload one, then sign this Travel Order.',
            ]);
        }

        // President signs — TO will be physically released by the Records Office
        $travelOrder->update([
            'status'    => 'pending_release',
            'issued_by' => $user->id,
            'issued_at' => now(),
        ]);

        Signature::create([
            'signable_type'            => TravelOrder::class,
            'signable_id'              => $travelOrder->id,
            'signer_id'                => $user->id,
            'purpose'                  => 'to_issuance',
            'signature_image_path'     => $user->signature_path,
            'document_hash'            => Signature::computeDocumentHash([
                'to_id'       => $travelOrder->id,
                'event_name'  => $travelOrder->event_name,
                'traveler_id' => $travelOrder->traveler_id,
                'date_from'   => $travelOrder->date_from->toDateString(),
                'date_to'     => $travelOrder->date_to->toDateString(),
                'approved_at' => now()->toIso8601String(),
            ]),
            'verification_code'        => Signature::generateVerificationCode(),
            'signer_name_snapshot'     => $user->name,
            'signer_position_snapshot' => $user->requested_position ?? 'University President',
            'ip_address'               => $request->ip(),
            'decision'                 => 'issued',
            'signed_at'                => now(),
        ]);

        return redirect()->route('president.travel-orders.index')
            ->with('success', "Travel Order {$travelOrder->to_number} signed. Forwarded to the Records Office for release.");
    }

    /**
     * Traveler attests they have returned from travel — submits a brief report.
     * Status: issued/active → returned (awaiting Records Office closure).
     */
    public function markReturned(Request $request, TravelOrder $travelOrder)
    {
        $user = auth()->user();

        $isTraveler = $travelOrder->traveler_id === $user->id
            || $travelOrder->travelers()->where('users.id', $user->id)->exists();

        if (!$isTraveler && $travelOrder->dean_id !== $user->id) {
            abort(403);
        }

        if (!in_array($travelOrder->status, ['issued', 'active'], true)) {
            return back()->with('error', 'Only officially issued Travel Orders can be marked as returned.');
        }

        $validated = $request->validate([
            'return_report' => ['required', 'string', 'min:20', 'max:2000'],
        ], [
            'return_report.required' => 'Please provide a brief attestation/report of your travel.',
            'return_report.min'      => 'Please provide a more detailed attestation (at least 20 characters).',
        ]);

        $travelOrder->update([
            'status'         => 'returned',
            'returned_at'    => now(),
            'return_report'  => $validated['return_report'],
            'returned_by'    => $user->id,
        ]);

        // Notify the endorsing dean that the traveler has returned (best-effort)
        if ($travelOrder->dean && $travelOrder->dean_id !== $user->id) {
            try {
                $travelOrder->dean->notify(new \App\Notifications\TravelReturnSubmitted($travelOrder, $user->name));
            } catch (\Throwable $e) {
                // Notifications shouldn't break the workflow
            }
        }

        return redirect()->route('travel-orders.show', $travelOrder)
            ->with('success', 'Travel attestation submitted. Your dean has been notified, and the Records Office will close your Travel Order.');
    }

    /**
     * Records Officer formally closes a returned Travel Order after verifying the attestation.
     * Status: returned → completed.
     */
    public function closeReturned(Request $request, TravelOrder $travelOrder)
    {
        $user = auth()->user();

        if (!$user->isRecordsOfficer()) {
            abort(403);
        }

        if ($travelOrder->status !== 'returned') {
            return back()->with('error', 'Only returned Travel Orders can be closed.');
        }

        $travelOrder->update([
            'status'              => 'completed',
            'records_released_at' => $travelOrder->records_released_at ?? now(),
            'records_released_by' => $travelOrder->records_released_by ?? $user->id,
            'records_remarks'     => trim(($travelOrder->records_remarks ? $travelOrder->records_remarks . "\n" : '') . 'Closed by Records Office on ' . now()->format('M j, Y g:i A')),
        ]);

        return redirect()->route('travel-orders.show', $travelOrder)
            ->with('success', 'Travel Order officially closed.');
    }

    // ──────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────

    private function authorizeDean($user): void
    {
        if (!in_array($user->role, ['dean', 'admin'], true)) {
            abort(403, 'Only deans may manage Travel Orders.');
        }
    }

    /**
     * Whether the given user may view/print a Travel Order.
     * Allowed: admin, President's Office, Records Officer, the issuing dean,
     * any endorsed traveler on the TO, and the VP who reviewed the endorsement.
     */
    private function canAccessTravelOrder($user, TravelOrder $travelOrder): bool
    {
        $isPresident    = $user->role === 'dean' && $user->department?->abbreviation === 'PRES';
        $isTravelerOnTO = $travelOrder->traveler_id === $user->id
            || $travelOrder->travelers()->where('users.id', $user->id)->exists();
        $isReviewer     = $travelOrder->endorsementLetter
            && $travelOrder->endorsementLetter->reviewed_by === $user->id;

        return $user->role === 'admin'
            || $isPresident
            || $user->isRecordsOfficer()
            || $travelOrder->dean_id === $user->id
            || $isTravelerOnTO
            || $isReviewer;
    }

    private function authorizePresident(): void
    {
        $user = auth()->user();
        if ($user->role !== 'dean' || $user->department?->abbreviation !== 'PRES') {
            abort(403, 'Only the University President may issue Travel Orders.');
        }
    }

    private function scopedTravelers($user): \Illuminate\Database\Eloquent\Builder
    {
        $q = User::where('status', 'active')->whereIn('role', ['traveler', 'dean', 'approver']);

        if ($user->role === 'dean' && $user->department?->abbreviation !== 'PRES') {
            $q->where('department_id', $user->department_id);
        }

        return $q;
    }
}
