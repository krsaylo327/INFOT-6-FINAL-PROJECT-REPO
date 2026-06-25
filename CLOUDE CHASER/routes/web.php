<?php

use App\Http\Controllers\RecordsOfficeController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AdminDepartmentController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TraceController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ReceivedInvitationController;
use App\Http\Controllers\TravelOrderController;
use App\Http\Controllers\TravelOrderAttachmentController;
use App\Http\Controllers\TravelRequestController;
use App\Http\Controllers\VehicleRequestController;
use App\Http\Controllers\ExpenseReportController;
use App\Http\Controllers\EndorsementLetterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('landing');
})->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->get('/account/pending', fn() => view('auth.pending'))->name('account.pending');

// Public trace page — signed URL, rate-limited, no auth required.
Route::middleware('throttle:30,1')
    ->get('/trace/{requestNo}', [TraceController::class, 'show'])
    ->name('trace.show');

// Public Travel Order checkpoint verification (signed URL, rate-limited)
Route::middleware('throttle:30,1')
    ->get('/verify-travel-order/{toNumber}', [App\Http\Controllers\TravelOrderTraceController::class, 'show'])
    ->name('travel-orders.trace');

// Public signature verification (no auth — anyone can verify a signed document)
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/verify-signature/{code}', [App\Http\Controllers\SignatureVerificationController::class, 'show'])
        ->name('signatures.verify');
    Route::get('/verify-signature/{code}/image.png', [App\Http\Controllers\SignatureVerificationController::class, 'image'])
        ->name('signatures.verify.image');
    Route::get('/verify-signature/{code}/qr.svg', [App\Http\Controllers\SignatureVerificationController::class, 'qr'])
        ->name('signatures.verify.qr');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/travel-requests', [TravelRequestController::class, 'index'])->name('travel-requests.index');
    Route::get('/travel-requests/create', [TravelRequestController::class, 'create'])->name('travel-requests.create');
    Route::post('/travel-requests', [TravelRequestController::class, 'store'])->name('travel-requests.store');
    Route::get('/travel-requests/{travelRequest}', [TravelRequestController::class, 'show'])->name('travel-requests.show');
    Route::get('/travel-requests/{travelRequest}/print', [TravelRequestController::class, 'print'])->name('travel-requests.print');
    Route::get('/travel-requests/{travelRequest}/qr', [TraceController::class, 'qr'])->name('travel-requests.qr');

    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::patch('/approvals/{approval}', [ApprovalController::class, 'update'])->name('approvals.update');

    // Admin — user management
    Route::get('/admin/users',                        [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/{user}',                 [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::post('/admin/users/{user}/approve',        [AdminUserController::class, 'approve'])->name('admin.users.approve');
    Route::post('/admin/users/{user}/reject',         [AdminUserController::class, 'reject'])->name('admin.users.reject');
    Route::post('/admin/users/{user}/disable',        [AdminUserController::class, 'disable'])->name('admin.users.disable');
    Route::post('/admin/users/{user}/enable',         [AdminUserController::class, 'enable'])->name('admin.users.enable');
    Route::post('/admin/users/{user}/reactivate',     [AdminUserController::class, 'reactivate'])->name('admin.users.reactivate');
    Route::patch('/admin/users/{user}',               [AdminUserController::class, 'update'])->name('admin.users.update');

    // Admin — department management
    Route::get('/admin/departments',              [AdminDepartmentController::class, 'index'])->name('admin.departments.index');
    Route::get('/admin/departments/{department}', [AdminDepartmentController::class, 'show'])->name('admin.departments.show');

    // Admin — analytics
    Route::get('/admin/analytics', [AdminAnalyticsController::class, 'index'])->name('admin.analytics.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Digital Signature
    Route::post('/profile/signature/upload', [ProfileController::class, 'uploadSignature'])->name('profile.signature.upload');
    Route::post('/profile/signature/draw',   [ProfileController::class, 'drawSignature'])->name('profile.signature.draw');
    Route::delete('/profile/signature',      [ProfileController::class, 'deleteSignature'])->name('profile.signature.delete');
    Route::get('/users/{user}/signature.png', [ProfileController::class, 'showSignature'])->name('profile.signature.show');

    // Notifications
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // File attachments
    Route::post('/travel-requests/{travelRequest}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');

    // Invitations (president forwards to dean)
    Route::get('/my-invitations', [InvitationController::class, 'myInvitations'])->name('invitations.my');
    Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::get('/invitations/create', [InvitationController::class, 'create'])->name('invitations.create');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::get('/invitations/inbox', [InvitationController::class, 'inbox'])->name('invitations.inbox');
    Route::post('/invitations/{invitation}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('/invitations/{invitation}/endorse', [InvitationController::class, 'endorse'])->name('invitations.endorse');
    Route::post('/invitations/{invitation}/reject', [InvitationController::class, 'reject'])->name('invitations.reject');
    Route::get('/invitations/{invitation}', [InvitationController::class, 'show'])->name('invitations.show');
    Route::get('/invitations/{invitation}/attachments/{attachment}/download', [InvitationController::class, 'downloadAttachment'])->name('invitations.attachments.download');
    Route::get('/invitations/{invitation}/attachments/{attachment}/view', [InvitationController::class, 'viewAttachment'])->name('invitations.attachments.view');

    // Received Invitations — President's Inbox (incoming from external orgs)
    Route::get('/received-invitations', [ReceivedInvitationController::class, 'index'])->name('received-invitations.index');
    Route::get('/received-invitations/{receivedInvitation}', [ReceivedInvitationController::class, 'show'])->name('received-invitations.show');
    Route::get('/received-invitations/{receivedInvitation}/forward', [ReceivedInvitationController::class, 'forwardForm'])->name('received-invitations.forward');
    Route::post('/received-invitations/{receivedInvitation}/forward', [ReceivedInvitationController::class, 'forward'])->name('received-invitations.forward.store');
    Route::post('/received-invitations/{receivedInvitation}/decline', [ReceivedInvitationController::class, 'decline'])->name('received-invitations.decline');
    Route::get('/received-invitations/{receivedInvitation}/attachments/{attachment}/download', [ReceivedInvitationController::class, 'downloadAttachment'])->name('received-invitations.attachments.download');
    Route::get('/received-invitations/{receivedInvitation}/attachments/{attachment}/view', [ReceivedInvitationController::class, 'viewAttachment'])->name('received-invitations.attachments.view');

    // Travel Orders — dean (official)
    Route::get('/travel-orders', [TravelOrderController::class, 'index'])->name('travel-orders.index');
    Route::get('/travel-orders/create', [TravelOrderController::class, 'create'])->name('travel-orders.create');
    Route::post('/travel-orders', [TravelOrderController::class, 'store'])->name('travel-orders.store');

    // Travel Orders — traveler (personal)
    Route::get('/my-travel-orders', [TravelOrderController::class, 'myIndex'])->name('travel-orders.my');
    Route::get('/my-travel-orders/create', [TravelOrderController::class, 'personalCreate'])->name('travel-orders.personal.create');
    Route::post('/my-travel-orders', [TravelOrderController::class, 'personalStore'])->name('travel-orders.personal.store');

    // Travel Orders — shared
    Route::get('/travel-orders/{travelOrder}', [TravelOrderController::class, 'show'])->name('travel-orders.show');
    Route::post('/travel-orders/{travelOrder}/submit', [TravelOrderController::class, 'submit'])->name('travel-orders.submit');
    Route::post('/travel-orders/{travelOrder}/return', [TravelOrderController::class, 'markReturned'])->name('travel-orders.return');
    Route::post('/travel-orders/{travelOrder}/close', [TravelOrderController::class, 'closeReturned'])->name('travel-orders.close');
    Route::get('/travel-orders/{travelOrder}/letter', [TravelOrderController::class, 'letter'])->name('travel-orders.letter');
    Route::get('/travel-orders/{travelOrder}/print', [TravelOrderController::class, 'printTo'])->name('travel-orders.print');
    Route::get('/travel-orders/{travelOrder}/qr', [App\Http\Controllers\TravelOrderTraceController::class, 'qr'])->name('travel-orders.qr');

    // President — Travel Order management (issue TO numbers)
    Route::get('/president/travel-orders', [TravelOrderController::class, 'adminIndex'])->name('president.travel-orders.index');
    Route::post('/president/travel-orders/{travelOrder}/issue', [TravelOrderController::class, 'issue'])->name('president.travel-orders.issue');

    // Vehicle requests (linked to a Travel Order)
    Route::post('/travel-orders/{travelOrder}/vehicle-request', [VehicleRequestController::class, 'store'])->name('vehicle-requests.store');
    Route::patch('/vehicle-requests/{vehicleRequest}/status', [VehicleRequestController::class, 'updateStatus'])->name('vehicle-requests.status');

    // Travel Order Attachments (waivers, receipts, other) — inline viewable
    Route::post('/travel-orders/{travelOrder}/attachments/{kind}', [TravelOrderAttachmentController::class, 'store'])
        ->name('travel-orders.attachments.store')->whereIn('kind', ['waiver', 'receipt', 'other']);
    Route::delete('/travel-order-attachments/{attachment}', [TravelOrderAttachmentController::class, 'destroy'])->name('travel-orders.attachments.destroy');
    Route::get('/travel-order-attachments/{attachment}/download', [TravelOrderAttachmentController::class, 'download'])->name('travel-orders.attachments.download');
    Route::get('/travel-order-attachments/{attachment}/view', [TravelOrderAttachmentController::class, 'viewAttachment'])->name('travel-orders.attachments.view');
    Route::post('/travel-orders/{travelOrder}/acknowledge-waiver', [TravelOrderAttachmentController::class, 'acknowledgeWaiver'])->name('travel-orders.acknowledge-waiver');

    // Expense Reports (after TO completed)
    Route::get('/travel-orders/{travelOrder}/expense-report/create', [ExpenseReportController::class, 'create'])->name('expense-reports.create');
    Route::post('/travel-orders/{travelOrder}/expense-report', [ExpenseReportController::class, 'store'])->name('expense-reports.store');
    Route::get('/expense-reports', [ExpenseReportController::class, 'adminIndex'])->name('expense-reports.admin-index');
    Route::get('/expense-reports/{expenseReport}', [ExpenseReportController::class, 'show'])->name('expense-reports.show');
    Route::post('/expense-reports/{expenseReport}/items', [ExpenseReportController::class, 'addItem'])->name('expense-reports.items.store');
    Route::delete('/expense-items/{expenseItem}', [ExpenseReportController::class, 'destroyItem'])->name('expense-reports.items.destroy');
    Route::get('/expense-items/{expenseItem}/receipt', [ExpenseReportController::class, 'viewItemReceipt'])->name('expense-reports.items.receipt');
    Route::post('/expense-reports/{expenseReport}/submit', [ExpenseReportController::class, 'submit'])->name('expense-reports.submit');
    Route::post('/expense-reports/{expenseReport}/review', [ExpenseReportController::class, 'review'])->name('expense-reports.review');

    // Assignment flow (approver/admin assigns, traveler acknowledges/declines)
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::post('/travel-requests/{travelRequest}/acknowledge', [AssignmentController::class, 'acknowledge'])
        ->name('assignments.acknowledge');
    Route::post('/travel-requests/{travelRequest}/decline', [AssignmentController::class, 'decline'])
        ->name('assignments.decline');

    // Records Office
    Route::get('/records-office', [RecordsOfficeController::class, 'index'])->name('records-office.index');
    Route::get('/records-office/outgoing', [RecordsOfficeController::class, 'outgoing'])->name('records-office.outgoing');
    Route::post('/records-office/travel-orders/{travelOrder}/release', [RecordsOfficeController::class, 'release'])->name('records-office.release');
    Route::get('/records-office/incoming', [RecordsOfficeController::class, 'incoming'])->name('records-office.incoming');
    Route::post('/records-office/incoming', [RecordsOfficeController::class, 'storeIncoming'])->name('records-office.incoming.store');

    // Endorsement Letters
    Route::get('/endorsement-letters', [EndorsementLetterController::class, 'index'])->name('endorsement-letters.index');
    Route::get('/my-endorsement-letters', [EndorsementLetterController::class, 'myIndex'])->name('endorsement-letters.my');
    Route::get('/my-endorsements', [EndorsementLetterController::class, 'staffIndex'])->name('endorsement-letters.staff');
    Route::get('/endorsement-letters/create/{invitation}', [EndorsementLetterController::class, 'create'])->name('endorsement-letters.create');
    Route::post('/endorsement-letters/{invitation}', [EndorsementLetterController::class, 'store'])->name('endorsement-letters.store');
    Route::get('/endorsement-letters/{endorsementLetter}', [EndorsementLetterController::class, 'show'])->name('endorsement-letters.show');
    Route::get('/endorsement-letters/{endorsementLetter}/letter', [EndorsementLetterController::class, 'letter'])->name('endorsement-letters.letter');

    // Travel Orders are now auto-created when VP approves an Endorsement Letter — no manual prep needed.
    Route::get('/endorsement-letters/{endorsementLetter}/edit', [EndorsementLetterController::class, 'edit'])->name('endorsement-letters.edit');
    Route::patch('/endorsement-letters/{endorsementLetter}', [EndorsementLetterController::class, 'update'])->name('endorsement-letters.update');
    Route::post('/endorsement-letters/{endorsementLetter}/review', [EndorsementLetterController::class, 'review'])->name('endorsement-letters.review');
});
