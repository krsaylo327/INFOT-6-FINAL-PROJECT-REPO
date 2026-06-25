<?php

namespace App\Http\Controllers;

use App\Models\ReceivedInvitation;
use App\Models\ReceivedInvitationAttachment;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecordsOfficeController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // DASHBOARD
    // ──────────────────────────────────────────────────────────────

    public function index()
    {
        $this->authorize(auth()->user());
        return redirect()->route('dashboard');
    }

    // ──────────────────────────────────────────────────────────────
    // OUTGOING — TO release queue
    // ──────────────────────────────────────────────────────────────

    public function outgoing()
    {
        $user = auth()->user();
        $this->authorize($user);

        // Queue 1: signed TOs awaiting physical release
        $pendingRelease = TravelOrder::with(['travelers', 'traveler', 'dean', 'department', 'issuer'])
            ->where('status', 'pending_release')
            ->oldest('issued_at')
            ->get();

        // Queue 2: returned TOs awaiting Records Office closure
        $pendingClosure = TravelOrder::with(['travelers', 'traveler', 'dean', 'department', 'returner'])
            ->where('status', 'returned')
            ->oldest('returned_at')
            ->get();

        $released = TravelOrder::with(['travelers', 'traveler', 'dean', 'department', 'recordsOfficer'])
            ->whereNotNull('records_released_at')
            ->latest('records_released_at')
            ->take(30)
            ->get();

        return view('records_office.outgoing', compact('user', 'pendingRelease', 'pendingClosure', 'released'));
    }

    /**
     * Records Officer releases the signed Travel Order to the traveler/dean
     * and logs it in the Outgoing Documents Register.
     * Status: pending_release → issued.
     */
    public function release(Request $request, TravelOrder $travelOrder)
    {
        $user = auth()->user();
        $this->authorize($user);

        if (!$travelOrder->isPendingRelease()) {
            return back()->with('error', 'Only Travel Orders pending Records Office release can be processed here.');
        }

        $validated = $request->validate([
            'records_remarks'  => ['nullable', 'string', 'max:500'],
            'received_by_name' => ['nullable', 'string', 'max:255'],
        ]);

        // Compose the register remark: who received it + any notes
        $remarkParts = [];
        if (!empty($validated['received_by_name'])) {
            $remarkParts[] = 'Received by: ' . $validated['received_by_name'];
        }
        if (!empty($validated['records_remarks'])) {
            $remarkParts[] = $validated['records_remarks'];
        }

        $travelOrder->update([
            'status'               => 'issued',
            'records_released_by'  => $user->id,
            'records_released_at'  => now(),
            'records_remarks'      => $remarkParts ? implode(' · ', $remarkParts) : null,
        ]);

        return redirect()->route('records-office.outgoing')
            ->with('success', "Travel Order {$travelOrder->to_number} released and logged in the Outgoing Documents Register.");
    }

    // ──────────────────────────────────────────────────────────────
    // INCOMING — log received invitations on behalf of the President
    // ──────────────────────────────────────────────────────────────

    public function incoming()
    {
        $user = auth()->user();
        $this->authorize($user);

        $items = ReceivedInvitation::with(['receiver', 'logger'])
            ->latest('received_at')
            ->latest('id')
            ->get();

        return view('records_office.incoming', compact('user', 'items'));
    }

    /**
     * Log a new incoming invitation from an external org.
     * Assigns it to the President's inbox automatically.
     */
    public function storeIncoming(Request $request)
    {
        $user = auth()->user();
        $this->authorize($user);

        $validated = $request->validate([
            'sender_org'        => ['required', 'string', 'max:255'],
            'sender_name'       => ['nullable', 'string', 'max:255'],
            'sender_email'      => ['nullable', 'email', 'max:255'],
            'sender_phone'      => ['nullable', 'string', 'max:50'],
            'event_name'        => ['required', 'string', 'max:255'],
            'event_venue'       => ['nullable', 'string', 'max:255'],
            'event_destination' => ['nullable', 'string', 'max:255'],
            'event_date_from'   => ['nullable', 'date'],
            'event_date_to'     => ['nullable', 'date', 'after_or_equal:event_date_from'],
            'event_type'        => ['nullable', 'in:academic,research'],
            'description'       => ['nullable', 'string', 'max:5000'],
            'received_at'       => ['required', 'date'],
            'attachments'       => ['nullable', 'array', 'max:5'],
            'attachments.*'     => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);

        // Route to the University President's inbox
        $president = User::where('role', 'dean')
            ->whereHas('department', fn($q) => $q->where('abbreviation', 'PRES'))
            ->where('status', 'active')
            ->first();

        if (!$president) {
            return back()->withErrors(['general' => 'No active University President found in the system.'])->withInput();
        }

        $received = ReceivedInvitation::create(array_merge($validated, [
            'received_by' => $president->id,
            'logged_by'   => $user->id,
            'status'      => 'new',
        ]));

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("received_invitations/{$received->id}", 'private');
                ReceivedInvitationAttachment::create([
                    'received_invitation_id' => $received->id,
                    'original_name'          => $file->getClientOriginalName(),
                    'stored_path'            => $path,
                    'mime_type'              => $file->getMimeType(),
                    'size'                   => $file->getSize(),
                    'uploaded_by'            => $user->id,
                ]);
            }
        }

        return redirect()->route('records-office.incoming')
            ->with('success', "Invitation from \"{$received->sender_org}\" logged and routed to the President's inbox.");
    }

    // ──────────────────────────────────────────────────────────────
    // HELPER
    // ──────────────────────────────────────────────────────────────

    private function authorize(User $user): void
    {
        if ($user->role !== 'records_officer') {
            abort(403, 'Only Records Officers may access this area.');
        }
    }
}
