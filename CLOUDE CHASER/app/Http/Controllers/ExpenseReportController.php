<?php

namespace App\Http\Controllers;

use App\Models\ExpenseItem;
use App\Models\ExpenseReport;
use App\Models\Signature;
use App\Models\TravelOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ExpenseReportController extends Controller
{
    /**
     * Admin/President index — list submitted expense reports for review.
     */
    public function adminIndex()
    {
        $user = auth()->user();
        $this->authorizeReviewer($user);

        $reports = ExpenseReport::with(['travelOrder', 'submitter', 'reviewer'])
            ->whereIn('status', ['submitted', 'approved', 'queried'])
            ->latest('submitted_at')
            ->get();

        return view('admin.expense_reports.index', compact('user', 'reports'));
    }

    /**
     * Show form to create new expense report linked to a TO.
     */
    public function create(TravelOrder $travelOrder)
    {
        $user = auth()->user();
        $this->authorizeOwner($user, $travelOrder);

        if (!$travelOrder->isCompleted()) {
            return redirect()->route('travel-orders.show', $travelOrder)
                ->with('error', 'Expense reports can only be submitted after the Travel Order is completed.');
        }

        if ($travelOrder->expenseReport) {
            return redirect()->route('expense-reports.show', $travelOrder->expenseReport);
        }

        return view('expense_reports.create', compact('user', 'travelOrder'));
    }

    /**
     * Store a new expense report (draft).
     */
    public function store(Request $request, TravelOrder $travelOrder)
    {
        $user = auth()->user();
        $this->authorizeOwner($user, $travelOrder);

        if (!$travelOrder->isCompleted()) {
            return redirect()->route('travel-orders.show', $travelOrder)
                ->with('error', 'Expense reports can only be submitted after the Travel Order is completed.');
        }

        if ($travelOrder->expenseReport) {
            return redirect()->route('expense-reports.show', $travelOrder->expenseReport);
        }

        $report = ExpenseReport::create([
            'travel_order_id' => $travelOrder->id,
            'submitted_by'    => $user->id,
            'total_amount'    => 0,
            'status'          => 'draft',
        ]);

        return redirect()->route('expense-reports.show', $report)
            ->with('success', 'Expense report draft started. Add expense items below.');
    }

    /**
     * Show an expense report.
     */
    public function show(ExpenseReport $expenseReport)
    {
        $user = auth()->user();
        $this->authorizeView($user, $expenseReport);

        $expenseReport->load(['travelOrder.traveler', 'travelOrder.dean', 'submitter', 'reviewer', 'items']);

        return view('expense_reports.show', compact('user', 'expenseReport'));
    }

    /**
     * Add an expense item to a draft report (with optional receipt upload).
     */
    public function addItem(Request $request, ExpenseReport $expenseReport)
    {
        $user = auth()->user();
        $this->authorizeOwner($user, $expenseReport->travelOrder);

        if (!$expenseReport->isDraft() && !$expenseReport->isQueried()) {
            return back()->with('error', 'Items can only be added while the report is in draft or has been queried.');
        }

        $validated = $request->validate([
            'description'  => ['required', 'string', 'max:255'],
            'amount'       => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'expense_date' => ['required', 'date'],
            'category'     => ['required', 'in:transport,lodging,meals,registration,other'],
            'receipt'      => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $receiptPath = null;
        $receiptOriginal = null;
        $receiptMime = null;
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $receiptPath = $file->store("expense_reports/{$expenseReport->id}/receipts", 'private');
            $receiptOriginal = $file->getClientOriginalName();
            $receiptMime = $file->getMimeType();
        }

        ExpenseItem::create([
            'expense_report_id'     => $expenseReport->id,
            'description'           => $validated['description'],
            'amount'                => $validated['amount'],
            'expense_date'          => $validated['expense_date'],
            'category'              => $validated['category'],
            'receipt_path'          => $receiptPath,
            'receipt_original_name' => $receiptOriginal,
            'receipt_mime_type'     => $receiptMime,
        ]);

        $expenseReport->recalculateTotal();

        return back()->with('success', 'Expense item added.');
    }

    /**
     * Delete an expense item (only while draft).
     */
    public function destroyItem(ExpenseItem $expenseItem)
    {
        $user = auth()->user();
        $report = $expenseItem->expenseReport;
        $this->authorizeOwner($user, $report->travelOrder);

        if (!$report->isDraft() && !$report->isQueried()) {
            return back()->with('error', 'Items can only be removed while the report is in draft or queried.');
        }

        if ($expenseItem->receipt_path) {
            Storage::disk('private')->delete($expenseItem->receipt_path);
        }

        $expenseItem->delete();
        $report->recalculateTotal();

        return back()->with('success', 'Expense item removed.');
    }

    /**
     * Submit the report for review.
     */
    public function submit(ExpenseReport $expenseReport)
    {
        $user = auth()->user();
        $this->authorizeOwner($user, $expenseReport->travelOrder);

        if (!$expenseReport->isDraft() && !$expenseReport->isQueried()) {
            return back()->with('error', 'Only draft or queried reports can be submitted.');
        }

        if ($expenseReport->items()->count() === 0) {
            return back()->with('error', 'Add at least one expense item before submitting.');
        }

        $expenseReport->recalculateTotal();
        $expenseReport->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        return redirect()->route('expense-reports.show', $expenseReport)
            ->with('success', 'Expense report submitted for review.');
    }

    /**
     * Admin reviews — approve or query (request more info).
     */
    public function review(Request $request, ExpenseReport $expenseReport)
    {
        $user = auth()->user();
        $this->authorizeReviewer($user);

        if (!$expenseReport->isSubmitted() && !$expenseReport->isQueried()) {
            return back()->with('error', 'Only submitted reports can be reviewed.');
        }

        $validated = $request->validate([
            'decision'     => ['required', 'in:approved,queried'],
            'remarks'      => ['nullable', 'string', 'max:1000'],
            'security_key' => ['required', 'string'],
        ]);

        if (!Hash::check($validated['security_key'], $user->password)) {
            return back()
                ->withErrors(['security_key' => 'Incorrect security key. Please enter your account password.'])
                ->withInput();
        }

        $expenseReport->update([
            'status'      => $validated['decision'],
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
            'remarks'     => $validated['remarks'] ?? null,
        ]);

        if ($user->hasSignature() && $validated['decision'] === 'approved') {
            Signature::create([
                'signable_type'            => ExpenseReport::class,
                'signable_id'              => $expenseReport->id,
                'signer_id'                => $user->id,
                'purpose'                  => 'expense_review',
                'signature_image_path'     => $user->signature_path,
                'document_hash'            => Signature::computeDocumentHash([
                    'report_id'    => $expenseReport->id,
                    'to_number'    => $expenseReport->travelOrder->to_number,
                    'total_amount' => $expenseReport->total_amount,
                    'item_count'   => $expenseReport->items()->count(),
                    'decision'     => $validated['decision'],
                    'reviewed_at'  => now()->toIso8601String(),
                ]),
                'verification_code'        => Signature::generateVerificationCode(),
                'signer_name_snapshot'     => $user->name,
                'signer_position_snapshot' => $user->requested_position ?? ($user->role === 'admin' ? 'Administrator' : 'University President'),
                'ip_address'               => $request->ip(),
                'decision_remarks'         => $validated['remarks'] ?? null,
                'decision'                 => $validated['decision'],
                'signed_at'                => now(),
            ]);
        }

        $action = $validated['decision'] === 'approved' ? 'approved' : 'returned for clarification';

        return redirect()->route('expense-reports.show', $expenseReport)
            ->with('success', "Expense report {$action}.");
    }

    /**
     * View an expense item's receipt inline.
     */
    public function viewItemReceipt(ExpenseItem $expenseItem)
    {
        $user = auth()->user();
        $report = $expenseItem->expenseReport;
        $this->authorizeView($user, $report);

        if (!$expenseItem->receipt_path) {
            abort(404);
        }

        return Storage::disk('private')->response(
            $expenseItem->receipt_path,
            $expenseItem->receipt_original_name ?? 'receipt',
            ['Content-Type' => $expenseItem->receipt_mime_type ?? 'application/octet-stream']
        );
    }

    private function authorizeOwner($user, TravelOrder $travelOrder): void
    {
        $allowed = $travelOrder->traveler_id === $user->id
            || $travelOrder->dean_id === $user->id
            || $user->role === 'admin';

        if (!$allowed) {
            abort(403);
        }
    }

    private function authorizeReviewer($user): void
    {
        $isPresident = $user->role === 'dean' && $user->department?->abbreviation === 'PRES';
        if ($user->role !== 'admin' && !$isPresident) {
            abort(403, 'Only the admin or President\'s Office may review expense reports.');
        }
    }

    private function authorizeView($user, ExpenseReport $report): void
    {
        $travelOrder = $report->travelOrder;
        $isPresident = $user->role === 'dean' && $user->department?->abbreviation === 'PRES';
        $allowed = $travelOrder->traveler_id === $user->id
            || $travelOrder->dean_id === $user->id
            || $report->submitted_by === $user->id
            || $user->role === 'admin'
            || $isPresident;

        if (!$allowed) {
            abort(403);
        }
    }
}
