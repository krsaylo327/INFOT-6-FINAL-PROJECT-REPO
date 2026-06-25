<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\TravelRequest;
use App\Models\User;
use App\Notifications\ApprovalRequested;
use App\Notifications\RequestDecided;

class ApprovalChainService
{
    /**
     * Cost thresholds (in PHP ₱) — used only when category is null.
     */
    public const LOW_COST_CEILING    = 5000;
    public const MEDIUM_COST_CEILING = 20000;

    /**
     * Build the ordered chain for this request.
     * Returns array keyed by level: ['approver' => User, 'is_noter' => bool]
     */
    public function buildChain(TravelRequest $tr): array
    {
        if ($tr->category === 'academic') {
            $vp = User::where('role', 'approver')
                ->where('approver_type', 'vp_academic')
                ->first();
            return $vp ? [1 => ['approver' => $vp, 'is_noter' => false]] : [];
        }

        if ($tr->category === 'research') {
            $rd  = User::where('role', 'approver')->where('approver_type', 'research_director')->first();
            $vpr = User::where('role', 'approver')->where('approver_type', 'vp_research')->first();
            $chain = [];
            if ($rd)  $chain[1] = ['approver' => $rd,  'is_noter' => true];
            if ($vpr) $chain[$rd ? 2 : 1] = ['approver' => $vpr, 'is_noter' => false];
            return $chain;
        }

        // Fallback: cost-based routing using approver_level
        $levels = $this->costLevelsFor($tr);
        $chain  = [];
        for ($level = 1; $level <= $levels; $level++) {
            $approver = User::where('role', 'approver')
                ->where('approver_level', $level)
                ->first();
            if ($approver) {
                $chain[$level] = ['approver' => $approver, 'is_noter' => false];
            }
        }
        return $chain;
    }

    /**
     * Cost-based level count — only used as fallback when category is null.
     */
    public function costLevelsFor(TravelRequest $tr): int
    {
        $cost = (float) $tr->estimated_cost;
        if ($cost <= self::LOW_COST_CEILING) return 1;
        if ($cost <= self::MEDIUM_COST_CEILING) return 2;
        return 3;
    }

    /**
     * Initialize the chain: create the Level-1 Approval row and notify.
     */
    public function initialize(TravelRequest $tr): ?Approval
    {
        $chain = $this->buildChain($tr);

        if (empty($chain) || !isset($chain[1])) {
            return null;
        }

        $entry = $chain[1];

        $approval = Approval::firstOrCreate(
            ['travel_request_id' => $tr->id, 'level' => 1],
            [
                'approver_id' => $entry['approver']->id,
                'action'      => 'pending',
                'is_noter'    => $entry['is_noter'],
            ]
        );

        $entry['approver']->notify(new ApprovalRequested($approval));

        return $approval;
    }

    /**
     * Advance the chain after the current approval step was acted on.
     * Works for both 'approved' and 'noted' actions.
     */
    public function advance(TravelRequest $tr, Approval $justActed): ?Approval
    {
        $chain     = $this->buildChain($tr);
        $nextLevel = $justActed->level + 1;

        if (!isset($chain[$nextLevel])) {
            $tr->update(['status' => 'approved']);
            $tr->user->notify(new RequestDecided($tr, 'approved'));
            return null;
        }

        $entry        = $chain[$nextLevel];
        $nextApprover = $entry['approver'];

        $tr->update(['status' => 'pending']);

        $nextApproval = Approval::create([
            'travel_request_id' => $tr->id,
            'approver_id'       => $nextApprover->id,
            'level'             => $nextLevel,
            'action'            => 'pending',
            'is_noter'          => $entry['is_noter'],
        ]);

        $nextApprover->notify(new ApprovalRequested($nextApproval));

        return $nextApproval;
    }

    /**
     * Short-circuit: reject at any level.
     */
    public function reject(TravelRequest $tr): void
    {
        $tr->update(['status' => 'rejected']);
    }

    /**
     * Get the currently-pending approval row (lowest level pending).
     */
    public function currentPendingApproval(TravelRequest $tr): ?Approval
    {
        return $tr->approvals()
            ->where('action', 'pending')
            ->orderBy('level')
            ->first();
    }
}
