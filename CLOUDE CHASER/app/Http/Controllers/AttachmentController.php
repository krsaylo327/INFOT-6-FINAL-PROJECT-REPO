<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Models\TravelRequestAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function store(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        $request->validate([
            'attachments'   => ['required', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);

        foreach ($request->file('attachments') as $file) {
            $path = $file->store("attachments/{$travelRequest->request_no}", 'public');

            TravelRequestAttachment::create([
                'travel_request_id' => $travelRequest->id,
                'original_name'     => $file->getClientOriginalName(),
                'stored_path'       => $path,
                'mime_type'         => $file->getMimeType(),
                'size'              => $file->getSize(),
                'uploaded_by'       => auth()->id(),
            ]);
        }

        return back()->with('success', 'Attachment(s) uploaded successfully.');
    }

    public function destroy(TravelRequestAttachment $attachment): RedirectResponse
    {
        $user = auth()->user();

        if ($attachment->uploaded_by !== $user->id && $user->role !== 'admin') {
            abort(403);
        }

        Storage::disk('public')->delete($attachment->stored_path);
        $attachment->delete();

        return back()->with('success', 'Attachment removed.');
    }

    public function download(TravelRequestAttachment $attachment): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($attachment->stored_path), 404);

        return Storage::disk('public')->download($attachment->stored_path, $attachment->original_name);
    }
}
