<?php

namespace App\Http\Controllers;

use App\Models\TravelOrder;
use App\Models\TravelOrderAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TravelOrderAttachmentController extends Controller
{
    /**
     * Upload an attachment of a given kind (waiver, receipt, other) to a Travel Order.
     */
    public function store(Request $request, TravelOrder $travelOrder, string $kind)
    {
        $user = auth()->user();
        $this->authorizeUpload($user, $travelOrder);

        if (!in_array($kind, ['waiver', 'receipt', 'other'], true)) {
            abort(404);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);

        $file = $request->file('file');
        $path = $file->store("travel_orders/{$travelOrder->id}/{$kind}", 'private');

        TravelOrderAttachment::create([
            'travel_order_id' => $travelOrder->id,
            'kind'            => $kind,
            'original_name'   => $file->getClientOriginalName(),
            'stored_path'     => $path,
            'mime_type'       => $file->getMimeType(),
            'size'            => $file->getSize(),
            'uploaded_by'     => $user->id,
        ]);

        $label = ucfirst($kind);
        return back()->with('success', "{$label} uploaded.");
    }

    /**
     * Delete an attachment (uploader or admin only).
     */
    public function destroy(TravelOrderAttachment $attachment)
    {
        $user = auth()->user();

        if ($attachment->uploaded_by !== $user->id && $user->role !== 'admin') {
            abort(403);
        }

        Storage::disk('private')->delete($attachment->stored_path);
        $attachment->delete();

        return back()->with('success', 'Attachment removed.');
    }

    /**
     * Download attachment.
     */
    public function download(TravelOrderAttachment $attachment)
    {
        $this->authorizeAccess(auth()->user(), $attachment);

        return Storage::disk('private')->download($attachment->stored_path, $attachment->original_name);
    }

    /**
     * View attachment inline (PDFs in browser PDF viewer, images natively).
     */
    public function viewAttachment(TravelOrderAttachment $attachment)
    {
        $this->authorizeAccess(auth()->user(), $attachment);

        return Storage::disk('private')->response(
            $attachment->stored_path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?? 'application/octet-stream']
        );
    }

    /**
     * Acknowledge the waiver terms (sets waiver_acknowledged_at).
     */
    public function acknowledgeWaiver(TravelOrder $travelOrder)
    {
        $user = auth()->user();
        $this->authorizeUpload($user, $travelOrder);

        if (!$travelOrder->waiver_required) {
            return back()->with('error', 'No waiver is required for this Travel Order.');
        }

        if ($travelOrder->waivers()->count() === 0) {
            return back()->with('error', 'Upload a signed waiver document before acknowledging.');
        }

        $travelOrder->update([
            'waiver_acknowledged'    => true,
            'waiver_acknowledged_at' => now(),
        ]);

        return back()->with('success', 'Waiver terms acknowledged.');
    }

    private function authorizeUpload($user, TravelOrder $travelOrder): void
    {
        $isPresident = $user->role === 'dean' && $user->department?->abbreviation === 'PRES';
        $allowed = $travelOrder->dean_id === $user->id
            || $travelOrder->traveler_id === $user->id
            || $user->role === 'admin'
            || $isPresident;

        if (!$allowed) {
            abort(403);
        }
    }

    private function authorizeAccess($user, TravelOrderAttachment $attachment): void
    {
        $travelOrder = $attachment->travelOrder;
        $isPresident = $user->role === 'dean' && $user->department?->abbreviation === 'PRES';
        $allowed = $travelOrder->dean_id === $user->id
            || $travelOrder->traveler_id === $user->id
            || $travelOrder->travelers()->where('users.id', $user->id)->exists()
            || $user->role === 'admin'
            || $isPresident;

        if (!$allowed) {
            abort(403);
        }
    }
}
