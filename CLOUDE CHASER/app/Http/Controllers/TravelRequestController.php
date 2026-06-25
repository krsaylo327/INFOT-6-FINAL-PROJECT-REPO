<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\TravelRequest;
use App\Models\TravelRequestAttachment;
use App\Services\ApprovalChainService;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TravelRequestController extends Controller
{
    public function __construct(
        protected ApprovalChainService $chain,
    ) {}

    public function index()
    {
        $user = auth()->user();

        $requests = TravelRequest::with(['department', 'user'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return view('travel_requests.index', compact('user', 'requests'));
    }

    public function create()
    {
        $user = auth()->user();
        $departments = Department::orderBy('name')->get();

        return view('travel_requests.create', compact('user', 'departments'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'department_id'  => ['required', 'exists:departments,id'],
            'category'       => ['required', 'in:academic,research'],
            'destination'    => ['required', 'string', 'max:255'],
            'purpose'        => ['required', 'string', 'min:20'],
            'date_from'      => ['required', 'date', 'after_or_equal:today'],
            'date_to'        => ['required', 'date', 'after_or_equal:date_from'],
            'estimated_cost' => ['required', 'numeric', 'min:0'],
            'attachments'    => ['nullable', 'array', 'max:5'],
            'attachments.*'  => ['file', 'max:5120', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);

        $tripDays = Carbon::parse($validated['date_from'])->diffInDays($validated['date_to']);
        if ($tripDays > 30) {
            session()->flash('compliance_warning', 'Trip duration exceeds 30 days. Ensure this complies with university travel policy.');
        } elseif ((float) $validated['estimated_cost'] > 100000) {
            session()->flash('compliance_warning', 'High-cost travel request (>₱100,000). Ensure proper justification is documented.');
        }

        $travelRequest = TravelRequest::create([
            'request_no'     => 'TR-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
            'user_id'        => $user->id,
            'department_id'  => $validated['department_id'],
            'category'       => $validated['category'],
            'destination'    => $validated['destination'],
            'purpose'        => $validated['purpose'],
            'date_from'      => $validated['date_from'],
            'date_to'        => $validated['date_to'],
            'estimated_cost' => $validated['estimated_cost'],
            'type'           => 'self',
            'status'         => 'pending',
            'submitted_at'   => now(),
        ]);

        $firstApproval = $this->chain->initialize($travelRequest);

        AuditLogger::log('request.submitted', $travelRequest, [
            'category'                => $travelRequest->category,
            'first_level_approver_id' => $firstApproval?->approver_id,
            'estimated_cost'          => (float) $travelRequest->estimated_cost,
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store("attachments/{$travelRequest->request_no}", 'public');
            TravelRequestAttachment::create([
                'travel_request_id' => $travelRequest->id,
                'original_name'     => $file->getClientOriginalName(),
                'stored_path'       => $path,
                'mime_type'         => $file->getMimeType(),
                'size'              => $file->getSize(),
                'uploaded_by'       => $user->id,
            ]);
        }

        return redirect()
            ->route('travel-requests.show', $travelRequest)
            ->with('success', 'Travel request submitted successfully.');
    }

    public function show(TravelRequest $travelRequest)
    {
        $user = auth()->user();

        $isOwner = $travelRequest->user_id === $user->id;
        $isApprover = $travelRequest->approvals()->where('approver_id', $user->id)->exists();
        $isAdmin = $user->role === 'admin';

        if (!$isOwner && !$isApprover && !$isAdmin) {
            abort(403);
        }

        $travelRequest->load(['department', 'user', 'approvals.approver', 'auditLogs.user', 'attachments', 'travelOrder']);

        $logs = $travelRequest->auditLogs()->with('user')->latest()->get();

        $traceUrl = \App\Http\Controllers\TraceController::signedTraceUrl($travelRequest);

        return view('travel_requests.show', compact('user', 'travelRequest', 'logs', 'traceUrl'));
    }

    /**
     * Printable Travel Order with embedded QR. Same authorization as show().
     */
    public function print(TravelRequest $travelRequest)
    {
        $user = auth()->user();

        $isOwner = $travelRequest->user_id === $user->id;
        $isApprover = $travelRequest->approvals()->where('approver_id', $user->id)->exists();
        $isAdmin = $user->role === 'admin';

        if (!$isOwner && !$isApprover && !$isAdmin) {
            abort(403);
        }

        $travelRequest->load(['department', 'user', 'approvals.approver', 'assigner']);

        return view('travel_requests.print', compact('travelRequest'));
    }
}
