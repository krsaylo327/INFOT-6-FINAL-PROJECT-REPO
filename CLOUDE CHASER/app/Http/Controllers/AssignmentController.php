<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Models\User;
use App\Notifications\TripAssigned;
use App\Services\ApprovalChainService;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssignmentController extends Controller
{
    /**
     * Roles that are allowed to assign travel to someone else.
     */
    private const ASSIGNER_ROLES = ['approver', 'admin', 'dean'];

    public function __construct(
        protected ApprovalChainService $chain,
    ) {}

    /**
     * List of assignments the current assigner has made.
     */
    public function index()
    {
        $user = auth()->user();
        $this->authorizeAssigner($user);

        $assignments = TravelRequest::with(['user', 'department', 'assigner'])
            ->where('type', 'assigned')
            ->where('assigned_by', $user->id)
            ->latest()
            ->get();

        return view('assignments.index', compact('user', 'assignments'));
    }

    /**
     * Show the form to assign a travel to a specific traveler.
     */
    public function create()
    {
        $user = auth()->user();
        $this->authorizeAssigner($user);

        $travelers = $this->scopedTravelers($user)->with('department')->orderBy('name')->get();

        return view('assignments.create', compact('user', 'travelers'));
    }

    /**
     * Store a newly assigned travel request.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $this->authorizeAssigner($user);

        $validated = $request->validate([
            'user_id'        => ['required', 'exists:users,id'],
            'category'       => ['required', 'in:academic,research'],
            'destination'    => ['required', 'string', 'max:255'],
            'purpose'        => ['required', 'string', 'min:20'],
            'date_from'      => ['required', 'date', 'after_or_equal:today'],
            'date_to'        => ['required', 'date', 'after_or_equal:date_from'],
            'estimated_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $tripDays = Carbon::parse($validated['date_from'])->diffInDays($validated['date_to']);
        if ($tripDays > 30) {
            session()->flash('compliance_warning', 'Trip duration exceeds 30 days. Ensure this complies with university travel policy.');
        } elseif ((float) $validated['estimated_cost'] > 100000) {
            session()->flash('compliance_warning', 'High-cost travel request (>₱100,000). Ensure proper justification is documented.');
        }

        $traveler = User::findOrFail($validated['user_id']);

        if ($traveler->role !== 'traveler') {
            return back()
                ->withErrors(['user_id' => 'You can only assign travel to users with the traveler role.'])
                ->withInput();
        }

        if (!$traveler->department_id) {
            return back()
                ->withErrors(['user_id' => 'This traveler has no department assigned. Please update their profile first.'])
                ->withInput();
        }

        // Deans can only assign within their department (unless President's Office)
        if ($user->role === 'dean' && $user->department?->abbreviation !== 'PRES') {
            if ($traveler->department_id !== $user->department_id) {
                return back()
                    ->withErrors(['user_id' => 'You can only assign travel to staff in your department.'])
                    ->withInput();
            }
        }

        $travelRequest = TravelRequest::create([
            'request_no'     => 'TR-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
            'user_id'        => $traveler->id,
            'department_id'  => $traveler->department_id,
            'category'       => $validated['category'],
            'destination'    => $validated['destination'],
            'purpose'        => $validated['purpose'],
            'date_from'      => $validated['date_from'],
            'date_to'        => $validated['date_to'],
            'estimated_cost' => $validated['estimated_cost'],
            'status'         => 'assigned',
            'type'           => 'assigned',
            'assigned_by'    => $user->id,
            'submitted_at'   => null,
        ]);

        AuditLogger::log('assignment.created', $travelRequest, [
            'assigner_id' => $user->id,
            'traveler_id' => $traveler->id,
            'estimated_cost' => (float) $travelRequest->estimated_cost,
        ]);

        $traveler->notify(new TripAssigned($travelRequest));

        return redirect()
            ->route('assignments.index')
            ->with('success', "Assigned travel to {$traveler->name}. They will be notified to acknowledge.");
    }

    /**
     * Traveler acknowledges an assigned travel. This moves it into the
     * approval chain: status becomes "pending" and a Level-1 approval is created.
     */
    public function acknowledge(TravelRequest $travelRequest)
    {
        $user = auth()->user();

        if ($travelRequest->user_id !== $user->id) {
            abort(403, 'This assignment is not yours to acknowledge.');
        }

        if (!$travelRequest->needsAcknowledgement()) {
            return back()->with('error', 'This request does not need acknowledgement.');
        }

        $travelRequest->update([
            'acknowledged_at' => now(),
            'submitted_at'    => now(),
            'status'          => 'pending',
        ]);

        // Kick off the full approval chain (policy-driven).
        $firstApproval = $this->chain->initialize($travelRequest);

        AuditLogger::log('assignment.acknowledged', $travelRequest, [
            'traveler_id'             => $user->id,
            'category'                => $travelRequest->category,
            'first_level_approver_id' => $firstApproval?->approver_id,
        ]);

        return redirect()
            ->route('travel-requests.show', $travelRequest)
            ->with('success', 'Assignment acknowledged. Your travel request is now in the approval queue.');
    }

    /**
     * Traveler declines an assigned travel. Status becomes "declined" and the
     * approval chain is NOT started.
     */
    public function decline(TravelRequest $travelRequest, Request $request)
    {
        $user = auth()->user();

        if ($travelRequest->user_id !== $user->id) {
            abort(403, 'This assignment is not yours to decline.');
        }

        if (!$travelRequest->needsAcknowledgement()) {
            return back()->with('error', 'This request can no longer be declined.');
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $travelRequest->update([
            'status' => 'declined',
        ]);

        AuditLogger::log('assignment.declined', $travelRequest, [
            'traveler_id' => $user->id,
            'reason'      => $request->input('reason'),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Assignment declined. Your assigner has been notified.');
    }

    /**
     * Role gate: only approvers/admins/deans may assign travel to others.
     */
    private function authorizeAssigner($user): void
    {
        if (!in_array($user->role, self::ASSIGNER_ROLES, true)) {
            abort(403, 'You do not have permission to assign travel.');
        }
    }

    /**
     * Returns a query for travelers the given user is allowed to assign.
     * Deans are limited to their own department; President's Office and
     * approvers/admins see everyone.
     */
    private function scopedTravelers($user): \Illuminate\Database\Eloquent\Builder
    {
        $q = User::where('role', 'traveler')->where('status', 'active');

        if ($user->role === 'dean' && $user->department?->abbreviation !== 'PRES') {
            $q->where('department_id', $user->department_id);
        }

        return $q;
    }
}
