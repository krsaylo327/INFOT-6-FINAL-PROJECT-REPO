<?php

namespace App\Http\Controllers;

use App\Models\EndorsementLetter;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminAnalyticsController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $issuedStatuses = ['issued', 'active', 'returned', 'completed'];

        // ── Summary ──────────────────────────────────────────────────────
        $totalRequests = TravelOrder::count();
        $totalApproved = TravelOrder::whereIn('status', $issuedStatuses)->count();   // released
        $totalCompleted = TravelOrder::where('status', 'completed')->count();
        $totalPending  = TravelOrder::whereIn('status', ['draft', 'submitted', 'pending_signature', 'pending_release'])->count();
        $releaseRate   = $totalRequests > 0 ? round($totalApproved / $totalRequests * 100) : 0;

        // ── Monthly trend (last 12 months): created vs released ──────────
        $months           = [];
        $requestsPerMonth = [];
        $approvedPerMonth = [];
        for ($i = 11; $i >= 0; $i--) {
            $m        = Carbon::now()->subMonths($i);
            $months[] = $m->format('M Y');
            $requestsPerMonth[] = TravelOrder::whereYear('created_at', $m->year)
                ->whereMonth('created_at', $m->month)->count();
            $approvedPerMonth[] = TravelOrder::whereNotNull('records_released_at')
                ->whereYear('records_released_at', $m->year)
                ->whereMonth('records_released_at', $m->month)->count();
        }

        // ── Status distribution ───────────────────────────────────────────
        $chartStatuses = TravelOrder::selectRaw('status, count(*) as total')
            ->groupBy('status')->get()->pluck('total', 'status')->toArray();

        // ── Travel Orders by department (top 8) ──────────────────────────
        $deptRows = TravelOrder::selectRaw('department_id, count(*) as total')
            ->with('department')
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->take(8)->get();
        $chartDeptLabels = $deptRows->map(fn ($r) => $r->department->abbreviation ?? $r->department->name ?? 'Unknown')->values()->toArray();
        $chartDeptCounts = $deptRows->pluck('total')->toArray();

        // ── Top destinations (top 8) ─────────────────────────────────────
        $destRows = TravelOrder::selectRaw('destination, count(*) as total')
            ->groupBy('destination')
            ->orderByDesc('total')
            ->take(8)->get();
        $chartDestLabels = $destRows->pluck('destination')->toArray();
        $chartDestCounts = $destRows->pluck('total')->toArray();

        // ── Endorsement breakdown (academic vs research, decisions) ──────
        $endorsementApproved = EndorsementLetter::where('status', 'approved')->count();
        $endorsementRejected = EndorsementLetter::where('status', 'rejected')->count();
        $endorsementPending  = EndorsementLetter::where('status', 'submitted')->count();

        // ── Top travelers by Travel Orders ───────────────────────────────
        $topTravelers = User::where('role', 'traveler')
            ->get()
            ->map(function ($u) {
                $u->to_count = TravelOrder::where('traveler_id', $u->id)
                    ->orWhereHas('travelers', fn ($q) => $q->where('users.id', $u->id))
                    ->count();
                return $u;
            })
            ->filter(fn ($u) => $u->to_count > 0)
            ->sortByDesc('to_count')
            ->take(5)
            ->values();

        return view('admin.analytics.index', compact(
            'totalRequests', 'totalApproved', 'totalCompleted', 'totalPending', 'releaseRate',
            'months', 'requestsPerMonth', 'approvedPerMonth',
            'chartStatuses',
            'chartDeptLabels', 'chartDeptCounts',
            'chartDestLabels', 'chartDestCounts',
            'endorsementApproved', 'endorsementRejected', 'endorsementPending',
            'topTravelers'
        ));
    }
}
