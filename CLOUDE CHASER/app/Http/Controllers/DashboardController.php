<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\EndorsementLetter;
use App\Models\ReceivedInvitation;
use App\Models\TravelOrder;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->role;

        $data = [
            'user' => $user,
            'role' => $role,
        ];

        if ($role === 'admin') {
            $data = array_merge($data, $this->adminData());
        } elseif ($role === 'approver') {
            $data = array_merge($data, $this->approverData($user));
        } elseif ($role === 'dean') {
            $data = array_merge($data, $this->deanData($user));
        } elseif ($role === 'records_officer') {
            $data = array_merge($data, $this->recordsOfficerData($user));
        } else {
            $data = array_merge($data, $this->travelerData($user));
        }

        $data['recentActivity'] = AuditLog::with('auditable')
            ->where('user_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        return view('dashboard', $data);
    }

    /**
     * Stats and lists for a TRAVELER.
     */
    private function travelerData($user): array
    {
        $base = TravelRequest::where('user_id', $user->id);

        $totalRequests    = (clone $base)->count();
        $pendingRequests  = (clone $base)->where('status', 'pending')->count();
        $approvedRequests = (clone $base)->where('status', 'approved')->count();
        $rejectedRequests = (clone $base)->where('status', 'rejected')->count();

        $upcomingTrips = TravelRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('date_from', '>=', now()->toDateString())
            ->orderBy('date_from')
            ->take(5)
            ->get();

        $recentRequests = TravelRequest::with(['department'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Assignments waiting for the traveler to acknowledge/decline
        $pendingAssignments = TravelRequest::with(['assigner', 'department'])
            ->where('user_id', $user->id)
            ->where('type', 'assigned')
            ->where('status', 'assigned')
            ->whereNull('acknowledged_at')
            ->latest()
            ->get();

        // Travel Orders auto-generated from endorsements, awaiting next step
        // (President's signature or physical release by Records Office).
        $upcomingTravelOrders = TravelOrder::with(['traveler', 'department', 'endorsementLetter.reviewer'])
            ->where(function ($q) use ($user) {
                $q->where('traveler_id', $user->id)
                  ->orWhereHas('travelers', fn ($qq) => $qq->where('users.id', $user->id));
            })
            ->whereIn('status', ['pending_signature', 'pending_release'])
            ->latest()
            ->get();

        return compact(
            'totalRequests',
            'pendingRequests',
            'approvedRequests',
            'rejectedRequests',
            'upcomingTrips',
            'recentRequests',
            'pendingAssignments',
            'upcomingTravelOrders'
        );
    }

    /**
     * Stats and lists for an APPROVER.
     */
    private function approverData($user): array
    {
        $monthStart = Carbon::now()->startOfMonth();

        $pendingApprovals = Approval::where('approver_id', $user->id)
            ->where('action', 'pending')
            ->count();

        $approvedThisMonth = Approval::where('approver_id', $user->id)
            ->where('action', 'approved')
            ->where('acted_at', '>=', $monthStart)
            ->count();

        $rejectedThisMonth = Approval::where('approver_id', $user->id)
            ->where('action', 'rejected')
            ->where('acted_at', '>=', $monthStart)
            ->count();

        // Requests coming from the approver's department (if set)
        $departmentRequests = 0;
        if ($user->department_id) {
            $departmentRequests = TravelRequest::where('department_id', $user->department_id)
                ->count();
        }

        // Awaiting this approver's decision — the main queue
        $awaitingApproval = Approval::with(['travelRequest.user', 'travelRequest.department'])
            ->where('approver_id', $user->id)
            ->where('action', 'pending')
            ->latest()
            ->take(8)
            ->get();

        // Recent decisions I took
        $recentDecisions = Approval::with(['travelRequest.user'])
            ->where('approver_id', $user->id)
            ->whereIn('action', ['approved', 'rejected'])
            ->whereNotNull('acted_at')
            ->latest('acted_at')
            ->take(5)
            ->get();

        // Assignments I issued that are still waiting on the traveler
        $assignmentsPendingAck = TravelRequest::where('assigned_by', $user->id)
            ->where('type', 'assigned')
            ->where('status', 'assigned')
            ->whereNull('acknowledged_at')
            ->count();

        return compact(
            'pendingApprovals',
            'approvedThisMonth',
            'rejectedThisMonth',
            'departmentRequests',
            'awaitingApproval',
            'recentDecisions',
            'assignmentsPendingAck'
        );
    }

    /**
     * Stats and lists for a DEAN.
     * President's Office dean (abbreviation = PRES) sees all departments.
     */
    private function deanData($user): array
    {
        $isPres = $user->department?->abbreviation === 'PRES';

        $staffQuery = User::where('role', 'traveler')->where('status', 'active');
        if (!$isPres) {
            $staffQuery->where('department_id', $user->department_id);
        }

        $totalStaff = (clone $staffQuery)->count();
        $staff      = (clone $staffQuery)->with('department')->orderBy('name')->get();

        $assignedBase   = TravelRequest::where('assigned_by', $user->id)->where('type', 'assigned');
        $totalAssigned  = (clone $assignedBase)->count();
        $pendingAck     = (clone $assignedBase)->where('status', 'assigned')->whereNull('acknowledged_at')->count();

        $recentAssignments = TravelRequest::with(['user', 'department'])
            ->where('assigned_by', $user->id)
            ->where('type', 'assigned')
            ->latest()
            ->take(8)
            ->get();

        // ── Endorsement analytics (for the college dean) ──────────────────
        $endorsementBase    = EndorsementLetter::where('dean_id', $user->id);
        $endorsementsTotal  = (clone $endorsementBase)->count();
        $endorsementsPending = (clone $endorsementBase)->where('status', 'submitted')->count();
        $endorsementsApproved = (clone $endorsementBase)->where('status', 'approved')->count();
        $endorsementsRejected = (clone $endorsementBase)->where('status', 'rejected')->count();

        // Travel Orders generated from this dean's endorsements
        $deanTOBase     = TravelOrder::where('dean_id', $user->id);
        $tosActive      = (clone $deanTOBase)->whereIn('status', ['pending_signature', 'pending_release', 'issued', 'active'])->count();
        $tosCompleted   = (clone $deanTOBase)->whereIn('status', ['returned', 'completed'])->count();

        // Travelers currently on official travel (released, not yet returned)
        $staffOnTravel  = (clone $deanTOBase)->whereIn('status', ['issued', 'active'])->count();

        return compact(
            'isPres', 'totalStaff', 'staff', 'totalAssigned', 'pendingAck', 'recentAssignments',
            'endorsementsTotal', 'endorsementsPending', 'endorsementsApproved', 'endorsementsRejected',
            'tosActive', 'tosCompleted', 'staffOnTravel'
        );
    }

    /**
     * Stats and quick-access data for the RECORDS OFFICER.
     */
    private function recordsOfficerData($user): array
    {
        $pendingRelease = TravelOrder::where('status', 'pending_release')->count();
        $pendingClosure = TravelOrder::where('status', 'returned')->count();

        $pendingIncoming = ReceivedInvitation::where('status', 'new')->count();

        $releasedThisMonth = TravelOrder::where('records_released_by', $user->id)
            ->whereMonth('records_released_at', now()->month)
            ->whereYear('records_released_at', now()->year)
            ->count();

        $totalLoggedByMe = ReceivedInvitation::where('logged_by', $user->id)->count();

        $pendingQueue = TravelOrder::with(['traveler', 'dean', 'department'])
            ->whereIn('status', ['pending_release', 'returned'])
            ->oldest('updated_at')
            ->take(5)
            ->get();

        $recentReleased = TravelOrder::with(['traveler', 'department'])
            ->where('records_released_by', $user->id)
            ->latest('records_released_at')
            ->take(5)
            ->get();

        $recentIncoming = ReceivedInvitation::where('logged_by', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return compact(
            'pendingRelease', 'pendingClosure', 'pendingIncoming',
            'releasedThisMonth', 'totalLoggedByMe',
            'pendingQueue', 'recentReleased', 'recentIncoming'
        );
    }

    /**
     * Stats and lists for an ADMIN (user-management focused).
     */
    private function adminData(): array
    {
        $totalUsers       = User::count();
        $totalDepartments = Department::count();
        $pendingUsers     = User::where('status', 'pending')->count();
        $activeUsers      = User::where('status', 'active')->count();

        $recentRegistrations = User::with('department')->latest()->take(10)->get();

        // Chart: users per department (top 8)
        $deptRows        = User::selectRaw('department_id, count(*) as total')
            ->with('department')
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->take(8)
            ->get();
        $chartDeptLabels = $deptRows->map(fn ($r) => $r->department->abbreviation ?? $r->department->name ?? 'Unknown')->values()->toArray();
        $chartDeptCounts = $deptRows->pluck('total')->toArray();

        // Chart: users by role
        $roleRows   = User::selectRaw('role, count(*) as total')->groupBy('role')->get();
        $chartRoles = $roleRows->pluck('total', 'role')->toArray();

        return compact(
            'totalUsers',
            'totalDepartments',
            'pendingUsers',
            'activeUsers',
            'recentRegistrations',
            'chartDeptLabels',
            'chartDeptCounts',
            'chartRoles'
        );
    }
}
