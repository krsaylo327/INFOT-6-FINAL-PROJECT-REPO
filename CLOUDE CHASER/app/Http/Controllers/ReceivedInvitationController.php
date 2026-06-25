<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\InvitationAttachment;
use App\Models\ReceivedInvitation;
use App\Models\ReceivedInvitationAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReceivedInvitationController extends Controller
{
    /**
     * Inbox — President's list of received invitations.
     */
    public function index()
    {
        $user = auth()->user();
        $this->authorizePresident($user);

        $received = ReceivedInvitation::with(['attachments', 'forwardedInvitations.assignedDean', 'logger'])
            ->where('received_by', $user->id)
            ->latest('received_at')
            ->latest('id')
            ->get();

        return view('received_invitations.index', compact('user', 'received'));
    }

    /**
     * View a received invitation detail (President or the Records Officer who logged it).
     */
    public function show(ReceivedInvitation $receivedInvitation)
    {
        $user = auth()->user();
        $this->authorizePresidentOrLogger($user, $receivedInvitation);

        $receivedInvitation->load(['attachments', 'forwardedInvitations.assignedDean.department', 'logger']);

        return view('received_invitations.show', compact('user', 'receivedInvitation'));
    }

    /**
     * Form to forward a received invitation to dean(s).
     */
    public function forwardForm(ReceivedInvitation $receivedInvitation)
    {
        $user = auth()->user();
        $this->authorizePresident($user);

        if ($receivedInvitation->received_by !== $user->id) {
            abort(403);
        }

        if (!$receivedInvitation->isNew()) {
            return redirect()->route('received-invitations.show', $receivedInvitation)
                ->with('error', 'This invitation has already been forwarded or declined.');
        }

        $deans = User::where('role', 'dean')
            ->where('status', 'active')
            ->where('id', '!=', $user->id)
            ->with('department')
            ->orderBy('name')
            ->get();

        $receivedInvitation->load('attachments');

        return view('received_invitations.forward', compact('user', 'receivedInvitation', 'deans'));
    }

    /**
     * Forward a received invitation to one or more deans.
     * Creates Invitation records and copies attachments.
     */
    public function forward(Request $request, ReceivedInvitation $receivedInvitation)
    {
        $user = auth()->user();
        $this->authorizePresident($user);

        if ($receivedInvitation->received_by !== $user->id) {
            abort(403);
        }

        if (!$receivedInvitation->isNew()) {
            return back()->with('error', 'This invitation has already been forwarded or declined.');
        }

        $validated = $request->validate([
            'assigned_to'        => ['required', 'array', 'min:1'],
            'assigned_to.*'      => ['exists:users,id'],
            'type'               => ['required', 'in:academic,research'],
            'additional_details' => ['nullable', 'string', 'max:2000'],
            'attachments'        => ['nullable', 'array', 'max:5'],
            'attachments.*'      => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);

        $recipients = User::whereIn('id', $validated['assigned_to'])
            ->where('role', 'dean')
            ->where('status', 'active')
            ->get();

        if ($recipients->isEmpty()) {
            return back()->withErrors(['assigned_to' => 'No valid deans found.'])->withInput();
        }

        $details = $receivedInvitation->description ?? '';
        if (!empty($validated['additional_details'])) {
            $details .= "\n\n— Notes from President's Office —\n" . $validated['additional_details'];
        }

        $payload = [
            'received_invitation_id' => $receivedInvitation->id,
            'issued_by'              => $user->id,
            'event_name'             => $receivedInvitation->event_name,
            'type'                   => $validated['type'],
            'destination'            => $receivedInvitation->event_destination,
            'venue'                  => $receivedInvitation->event_venue,
            'date_from'              => $receivedInvitation->event_date_from,
            'date_to'                => $receivedInvitation->event_date_to,
            'details'                => $details ?: '(see attached invitation document)',
        ];

        $sourceAttachments   = $receivedInvitation->attachments;
        $additionalFiles     = $request->file('attachments', []);

        foreach ($recipients as $dean) {
            $invitation = Invitation::create(array_merge($payload, ['assigned_to' => $dean->id]));

            // Copy existing inbox attachments
            foreach ($sourceAttachments as $sourceAtt) {
                $newPath = "invitations/{$invitation->id}/" . basename($sourceAtt->stored_path);
                Storage::disk('private')->copy($sourceAtt->stored_path, $newPath);
                InvitationAttachment::create([
                    'invitation_id' => $invitation->id,
                    'original_name' => $sourceAtt->original_name,
                    'stored_path'   => $newPath,
                    'mime_type'     => $sourceAtt->mime_type,
                    'size'          => $sourceAtt->size,
                    'uploaded_by'   => $user->id,
                ]);
            }

            // Store additional files uploaded at forward time
            foreach ($additionalFiles as $file) {
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

        $receivedInvitation->update(['status' => 'forwarded']);

        $message = $recipients->count() > 1
            ? "Invitation forwarded to {$recipients->count()} deans."
            : "Invitation forwarded to {$recipients->first()->name}.";

        return redirect()->route('received-invitations.show', $receivedInvitation)
            ->with('success', $message);
    }

    /**
     * Decline a received invitation (don't forward).
     */
    public function decline(Request $request, ReceivedInvitation $receivedInvitation)
    {
        $user = auth()->user();
        $this->authorizePresident($user);

        if ($receivedInvitation->received_by !== $user->id) {
            abort(403);
        }

        if (!$receivedInvitation->isNew()) {
            return back()->with('error', 'This invitation has already been forwarded or declined.');
        }

        $request->validate([
            'declined_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $receivedInvitation->update([
            'status'          => 'declined',
            'declined_reason' => $request->input('declined_reason'),
        ]);

        return redirect()->route('received-invitations.show', $receivedInvitation)
            ->with('success', 'Invitation declined.');
    }

    /**
     * Download an attachment.
     */
    public function downloadAttachment(ReceivedInvitation $receivedInvitation, ReceivedInvitationAttachment $attachment)
    {
        $user = auth()->user();
        $this->authorizePresidentOrLogger($user, $receivedInvitation);

        if ($attachment->received_invitation_id !== $receivedInvitation->id) {
            abort(403);
        }

        return Storage::disk('private')->download($attachment->stored_path, $attachment->original_name);
    }

    /**
     * View an attachment inline (browser-rendered).
     */
    public function viewAttachment(ReceivedInvitation $receivedInvitation, ReceivedInvitationAttachment $attachment)
    {
        $user = auth()->user();
        $this->authorizePresidentOrLogger($user, $receivedInvitation);

        if ($attachment->received_invitation_id !== $receivedInvitation->id) {
            abort(403);
        }

        return Storage::disk('private')->response(
            $attachment->stored_path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?? 'application/octet-stream']
        );
    }

    private function authorizePresident($user): void
    {
        if ($user->role !== 'dean' || $user->department?->abbreviation !== 'PRES') {
            abort(403, 'Only the President\'s Office may access the inbox.');
        }
    }

    private function authorizePresidentOrLogger($user, ReceivedInvitation $item): void
    {
        $isPresident     = $user->role === 'dean' && $user->department?->abbreviation === 'PRES';
        $isLogger        = $user->role === 'records_officer' && $item->logged_by === $user->id;
        $isPresidentOwner = $isPresident && $item->received_by === $user->id;

        if (!$isPresidentOwner && !$isLogger) {
            abort(403);
        }
    }
}
