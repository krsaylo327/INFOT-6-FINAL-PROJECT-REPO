<?php

namespace App\Providers;

use App\Models\Approval;
use App\Models\EndorsementLetter;
use App\Models\Invitation;
use App\Models\ReceivedInvitation;
use App\Models\TravelOrder;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $user  = auth()->user();
            $count = $user && $user->role === 'admin'
                ? User::where('status', 'pending')->count()
                : 0;
            $view->with('pendingUsersCount', $count);

            $unreadNotifications = $user
                ? $user->unreadNotifications()->latest()->take(5)->get()
                : collect();
            $view->with('unreadNotifications', $unreadNotifications);
            $view->with('unreadCount', $unreadNotifications->count());

            // President's inbox pending count (received but not yet forwarded/declined)
            $pendingReceivedCount = ($user && $user->role === 'dean' && $user->department?->abbreviation === 'PRES')
                ? ReceivedInvitation::where('received_by', $user->id)->where('status', 'new')->count()
                : 0;
            $view->with('pendingReceivedCount', $pendingReceivedCount);

            // President's forwarded invitations awaiting a dean response (open status)
            $forwardedAwaitingCount = ($user && $user->role === 'dean' && $user->department?->abbreviation === 'PRES')
                ? Invitation::where('issued_by', $user->id)->where('status', 'open')->count()
                : 0;
            $view->with('forwardedAwaitingCount', $forwardedAwaitingCount);

            // President: Travel Orders awaiting their signature
            $pendingSignatureCount = ($user && $user->role === 'dean' && $user->department?->abbreviation === 'PRES')
                ? TravelOrder::where('status', 'pending_signature')->count()
                : 0;
            $view->with('pendingSignatureCount', $pendingSignatureCount);

            // Dean's inbox: open invitations awaiting their response
            $deanInboxCount = ($user && $user->role === 'dean' && $user->department?->abbreviation !== 'PRES')
                ? Invitation::where('assigned_to', $user->id)->where('status', 'open')->count()
                : 0;
            $view->with('deanInboxCount', $deanInboxCount);

            // Dean's endorsement letters that were just reviewed (approved or rejected) — needs attention
            $deanEndorsementUpdatesCount = ($user && $user->role === 'dean' && $user->department?->abbreviation !== 'PRES')
                ? EndorsementLetter::where('dean_id', $user->id)
                    ->whereIn('status', ['approved', 'rejected'])
                    ->whereNotNull('reviewed_at')
                    ->where('reviewed_at', '>=', now()->subDays(7))
                    ->count()
                : 0;
            $view->with('deanEndorsementUpdatesCount', $deanEndorsementUpdatesCount);

            // VPAA / VPREI pending endorsement letter count
            $pendingEndorsementsCount = 0;
            if ($user && $user->role === 'approver' && in_array($user->approver_type, ['vp_academic', 'vp_research'])) {
                $category = $user->approver_type === 'vp_research' ? 'research' : 'academic';
                $pendingEndorsementsCount = EndorsementLetter::where('category', $category)
                    ->where('status', 'submitted')
                    ->count();
            }
            $view->with('pendingEndorsementsCount', $pendingEndorsementsCount);

            // Approver: pending approvals assigned to them
            $pendingApprovalsCount = ($user && $user->role === 'approver')
                ? Approval::where('approver_id', $user->id)->where('action', 'pending')->count()
                : 0;
            $view->with('pendingApprovalsCount', $pendingApprovalsCount);

            // Records Officer: TOs at any stage requiring action (release or closure)
            $pendingReleaseCount = ($user && $user->role === 'records_officer')
                ? TravelOrder::whereIn('status', ['pending_release', 'returned'])->count()
                : 0;
            $view->with('pendingReleaseCount', $pendingReleaseCount);

            // Traveler: assignments awaiting their acknowledgement
            $pendingAckCount = ($user && $user->role === 'traveler')
                ? TravelRequest::where('user_id', $user->id)
                    ->where('status', 'assigned')
                    ->whereNull('acknowledged_at')
                    ->count()
                : 0;
            $view->with('pendingAckCount', $pendingAckCount);

            // Traveler: active endorsements they've been named on (submitted or approved)
            $myEndorsementsCount = ($user && $user->role === 'traveler')
                ? EndorsementLetter::whereIn('status', ['submitted', 'approved'])
                    ->whereHas('staff', fn ($q) => $q->where('users.id', $user->id))
                    ->count()
                : 0;
            $view->with('myEndorsementsCount', $myEndorsementsCount);
        });
    }
}
