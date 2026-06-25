<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\InvitationAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvitationController extends Controller
{
    /**
     * President's list of invitations they have sent.
     */
    public function index()
    {
        $user = auth()->user();
        $this->authorizePresident($user);

        $invitations = Invitation::with(['assignedDean.department', 'travelOrder'])
            ->where('issued_by', $user->id)
            ->latest()
            ->get();

        return view('invitations.index', compact('user', 'invitations'));
    }

    /**
     * Form to forward a new invitation to a dean.
     */
    public function create()
    {
        $user = auth()->user();
        $this->authorizePresident($user);

        $deans = User::where('role', 'dean')
            ->where('status', 'active')
            ->where('id', '!=', $user->id)
            ->with('department')
            ->orderBy('name')
            ->get();

        return view('invitations.create', compact('user', 'deans'));
    }

    /**
     * Store and send a new invitation — to one specific dean or all deans.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $this->authorizePresident($user);

        $validated = $request->validate([
            'assigned_to'   => ['required', 'array', 'min:1'],
            'assigned_to.*' => ['exists:users,id'],
            'event_name'    => ['required', 'string', 'max:255'],
            'type'          => ['required', 'in:academic,research'],
            'destination'   => ['nullable', 'string', 'max:255'],
            'venue'         => ['nullable', 'string', 'max:255'],
            'date_from'     => ['nullable', 'date'],
            'date_to'       => ['nullable', 'date', 'after_or_equal:date_from'],
            'details'       => ['required', 'string', 'min:10'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);

        $recipients = User::whereIn('id', $validated['assigned_to'])
            ->where('role', 'dean')
            ->where('status', 'active')
            ->get();

        if ($recipients->isEmpty()) {
            return back()->withErrors(['assigned_to' => 'No valid deans found.'])->withInput();
        }

        $payload = [
            'issued_by'   => $user->id,
            'event_name'  => $validated['event_name'],
            'type'        => $validated['type'],
            'destination' => $validated['destination'] ?? null,
            'venue'       => $validated['venue'] ?? null,
            'date_from'   => $validated['date_from'] ?? null,
            'date_to'     => $validated['date_to'] ?? null,
            'details'     => $validated['details'],
        ];

        foreach ($recipients as $dean) {
            $invitation = Invitation::create(array_merge($payload, ['assigned_to' => $dean->id]));

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store("invitations/{$invitation->id}", 'private');
                    InvitationAttachment::create([
                        'invitation_id' => $invitation->id,
                        'original_name' => $file->getClientOriginalName(),
                        'stored_path'   => $path,
                        'mime_type'     => $file->getMimeType(),
                        'size'          => $file->getSize(),
                        'uploaded_by'   => $user->id,
                    ]);
                }
            }
        }

        $message = $recipients->count() > 1
            ? "Invitation forwarded to {$recipients->count()} deans."
            : "Invitation forwarded to {$recipients->first()->name}.";

        return redirect()->route('invitations.index')->with('success', $message);
    }

    /**
     * Dean accepts an invitation to attend personally — marks as accepted,
     * redirects to create TO. NO VPAA/VPREI approval needed.
     */
    public function accept(Invitation $invitation)
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        if ($invitation->assigned_to !== $user->id) {
            abort(403);
        }

        if (!$invitation->isOpen()) {
            return back()->with('error', 'This invitation has already been responded to.');
        }

        $invitation->update(['status' => 'accepted']);

        return redirect()
            ->route('travel-orders.create', ['invitation' => $invitation->id])
            ->with('success', 'Invitation accepted. You will attend personally. Create the Travel Order below.');
    }

    /**
     * Dean chooses to endorse staff instead of attending personally.
     * Redirects to the endorsement letter creation form.
     */
    public function endorse(Invitation $invitation)
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        if ($invitation->assigned_to !== $user->id) {
            abort(403);
        }

        if (!$invitation->canRespond()) {
            return back()->with('error', 'This invitation has already been responded to.');
        }

        return redirect()->route('endorsement-letters.create', ['invitation' => $invitation->id])
            ->with('info', 'Create an endorsement letter to assign staff for this invitation.');
    }

    /**
     * Dean rejects an invitation — marks as rejected with a reason.
     */
    public function reject(Request $request, Invitation $invitation)
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        if ($invitation->assigned_to !== $user->id) {
            abort(403);
        }

        if (!$invitation->isOpen() && !$invitation->isAccepted()) {
            return back()->with('error', 'This invitation cannot be rejected at this stage.');
        }

        $request->validate([
            'reject_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $invitation->update([
            'status'        => 'rejected',
            'reject_reason' => $request->input('reject_reason'),
        ]);

        return back()->with('success', 'Invitation declined.');
    }

    /**
     * Dean's inbox — invitations assigned to them.
     */
    public function inbox()
    {
        $user = auth()->user();
        $this->authorizeDean($user);

        $invitations = Invitation::with(['issuer', 'travelOrder', 'attachments'])
            ->where('assigned_to', $user->id)
            ->latest()
            ->get();

        return view('invitations.inbox', compact('user', 'invitations'));
    }

    /**
     * Show a single invitation (dean or president view).
     */
    public function show(Invitation $invitation)
    {
        $user = auth()->user();

        if ($invitation->issued_by !== $user->id && $invitation->assigned_to !== $user->id) {
            abort(403);
        }

        $invitation->load(['issuer', 'assignedDean.department', 'travelOrder.traveler', 'attachments']);

        return view('invitations.show', compact('user', 'invitation'));
    }

    /**
     * Download an invitation attachment (president or assigned dean only).
     */
    public function downloadAttachment(Invitation $invitation, InvitationAttachment $attachment)
    {
        $user = auth()->user();

        if ($invitation->issued_by !== $user->id && $invitation->assigned_to !== $user->id) {
            abort(403);
        }

        if ($attachment->invitation_id !== $invitation->id) {
            abort(404);
        }

        return Storage::disk('private')->download($attachment->stored_path, $attachment->original_name);
    }

    /**
     * View an invitation attachment inline (browser-rendered for PDF/images).
     */
    public function viewAttachment(Invitation $invitation, InvitationAttachment $attachment)
    {
        $user = auth()->user();

        if ($invitation->issued_by !== $user->id && $invitation->assigned_to !== $user->id) {
            abort(403);
        }

        if ($attachment->invitation_id !== $invitation->id) {
            abort(404);
        }

        return Storage::disk('private')->response(
            $attachment->stored_path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?? 'application/octet-stream']
        );
    }

    /**
     * Traveler's view of invitations that resulted in TOs they are listed on.
     */
    public function myInvitations()
    {
        $user = auth()->user();

        $invitations = Invitation::with(['issuer', 'assignedDean', 'travelOrder.travelers'])
            ->whereHas('travelOrder.travelers', fn ($q) => $q->where('users.id', $user->id))
            ->latest()
            ->get();

        return view('invitations.my_index', compact('user', 'invitations'));
    }

    private function authorizePresident($user): void
    {
        if ($user->role !== 'dean' || $user->department?->abbreviation !== 'PRES') {
            abort(403, 'Only the President\'s Office may forward invitations.');
        }
    }

    private function authorizeDean($user): void
    {
        if ($user->role !== 'dean') {
            abort(403);
        }
    }
}
