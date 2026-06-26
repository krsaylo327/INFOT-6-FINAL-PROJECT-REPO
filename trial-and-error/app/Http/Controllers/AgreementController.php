<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\Notification;
use App\Models\User;
use App\Models\WorkflowHistory;
use App\Services\AgreementDocumentStorageService;
use App\Services\AgreementWorkflowService;
use App\Support\AgreementWorkflowMap;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AgreementController extends Controller
{
    public function __construct(
        private AgreementWorkflowService $workflowService,
        private AgreementDocumentStorageService $documentStorageService,
    ) {}

    private function syncAgreementStatus(Agreement $agreement): Agreement
    {
        return $agreement->syncStatus();
    }

    private function roleDashboardTitle(string $role): string
    {
        $role = AgreementWorkflowMap::normalizeRole($role);

        return match ($role) {
            'admin', 'system_admin' => 'Admin Dashboard',
            'authorized_personnel' => 'Authorized Personnel Dashboard',
            'legal_assistant_ii' => 'Partner Coordinator Dashboard',
            'legal_assistant_iii' => 'Partner Coordinator Dashboard',
            'attorney' => 'Partner Coordinator Dashboard',
            'administrative_aid' => 'Partner Coordinator Dashboard',
            'president' => 'Partner Coordinator Dashboard',
            'coordinator' => 'Partner Coordinator Dashboard',
            default => $role === '' ? 'Dashboard' : ucwords(str_replace('_', ' ', $role)).' Dashboard',
        };
    }

    private function roleAssignedAgreements(string $role): array
    {
        if (AgreementWorkflowMap::normalizeRole($role) === 'authorized_personnel') {
            return [];
        }

        $statuses = $this->workflowStatusesForRole($role);

        if (empty($statuses)) {
            return [];
        }

        return Agreement::whereIn('workflow_status', $statuses)->latest()->get()->all();
    }

    private function useStrictUploaderIdentity(): bool
    {
        return (bool) config('agreements.strict_uploader_identity', false);
    }

    private function applyUploaderFilter($query, User $user): void
    {
        $query->where('uploaded_by_id', $user->id);

        if (! $this->useStrictUploaderIdentity()) {
            $query->orWhere('uploaded_by', $user->name);
        }
    }

    private function workflowStatusesForRole(string $normalizedRole): array
    {
        return AgreementWorkflowMap::statusesForRole($normalizedRole);
    }

    private function roleTokenForWorkflowStatus(string $workflowStatus): ?string
    {
        return AgreementWorkflowMap::roleForStatus($workflowStatus);
    }

    private function humanizeWorkflowStatus(?string $status): ?string
    {
        return AgreementWorkflowMap::humanizeStatus($status);
    }

    private function canViewDraftAgreements(string $normalizedRole): bool
    {
        return AgreementWorkflowMap::normalizeRole($normalizedRole) === 'authorized_personnel';
    }

    private function draftAgreementsForUser(User $user)
    {
        return Agreement::with(['versions.uploadedBy', 'workflowHistories'])
            ->where('status', 'draft')
            ->where(function ($query) use ($user): void {
                $query->where('submitted_by', $user->id)
                    ->orWhereHas('versions', function ($versionQuery) use ($user): void {
                        $versionQuery->where(function ($q) use ($user): void {
                            $this->applyUploaderFilter($q, $user);
                        });
                    });
            })
            ->latest()
            ->get();
    }

    private function decorateAgreementAccessContext(Collection $agreements, User $user): Collection
    {
        $normalizedRole = AgreementWorkflowMap::normalizeRole($user->role ?? '');
        $roleStatuses = $this->workflowStatusesForRole($normalizedRole);

        return $agreements->map(function (Agreement $agreement) use ($user, $roleStatuses) {
            $histories = $agreement->workflowHistories->sortBy('id')->values();

            $receivedEvent = $histories->first(function ($history) use ($roleStatuses) {
                return in_array($history->to_status, $roleStatuses, true);
            });

            $submittedEvent = $histories->last(function ($history) use ($user) {
                return strcasecmp((string) $history->performed_by, (string) $user->name) === 0
                    && in_array($history->action, ['Submitted', 'Forwarded'], true);
            });

            if ($agreement->submitted_by === $user->id && ! $submittedEvent) {
                $submittedEvent = $histories->first(function ($history): bool {
                    return in_array($history->action, ['Submitted', 'Forwarded'], true);
                });
            }

            $accessEvents = [];

            if ($receivedEvent) {
                $accessEvents[] = [
                    'type' => 'Received',
                    'tone' => 'bg-yellow-50 text-yellow-800',
                    'date' => optional($receivedEvent->created_at)->toISOString(),
                    'from' => $this->humanizeWorkflowStatus($receivedEvent->from_status),
                    'to' => null,
                ];
            }

            if ($submittedEvent || $agreement->submitted_by === $user->id) {
                $accessEvents[] = [
                    'type' => 'Submitted',
                    'tone' => 'bg-blue-50 text-blue-800',
                    'date' => optional($submittedEvent?->created_at)->toISOString(),
                    'from' => null,
                    'to' => $this->humanizeWorkflowStatus($submittedEvent?->to_status),
                ];
            }

            $agreement->setAttribute('access_events', $accessEvents);

            if ($receivedEvent && $submittedEvent) {
                $agreement->setAttribute('access_label', 'Received and Submitted');
                $agreement->setAttribute('access_tone', 'bg-green-50 text-green-800');
                $agreement->setAttribute('access_date', optional($submittedEvent->created_at)->toISOString());
            } elseif ($submittedEvent || $agreement->submitted_by === $user->id) {
                $agreement->setAttribute('access_label', 'Submitted');
                $agreement->setAttribute('access_tone', 'bg-blue-50 text-blue-800');
                $agreement->setAttribute('access_date', optional($submittedEvent?->created_at)->toISOString());
                $agreement->setAttribute('access_to', $this->humanizeWorkflowStatus($submittedEvent?->to_status));
            } elseif ($receivedEvent) {
                $agreement->setAttribute('access_label', 'Received');
                $agreement->setAttribute('access_tone', 'bg-yellow-50 text-yellow-800');
                $agreement->setAttribute('access_date', optional($receivedEvent->created_at)->toISOString());
                $agreement->setAttribute('access_from', $this->humanizeWorkflowStatus($receivedEvent->from_status));
            }

            return $agreement;
        });
    }

    private function accessibleAgreementsForUser(User $user)
    {
        $normalizedRole = AgreementWorkflowMap::normalizeRole($user->role ?? '');
        $normalizedRole = AgreementWorkflowMap::normalizeRole($normalizedRole);
        $workflowStatuses = $this->workflowStatusesForRole($normalizedRole);
        $canViewDrafts = $this->canViewDraftAgreements($normalizedRole);
        $lowerName = strtolower((string) $user->name);

        $agreements = Agreement::query()
            ->with(['versions.uploadedBy', 'workflowHistories'])
            ->where('submitted_by', $user->id)
            ->orWhereHas('versions', function ($query) use ($user): void {
                $query->where(function ($q) use ($user) {
                    $this->applyUploaderFilter($q, $user);
                });
            })
            ->orWhere(function ($query) use ($workflowStatuses): void {
                if (! empty($workflowStatuses)) {
                    $query->whereIn('workflow_status', $workflowStatuses);
                }
            })
            ->orWhereHas('workflowHistories', function ($query) use ($lowerName, $workflowStatuses): void {
                $query->whereRaw('LOWER(performed_by) = ?', [$lowerName]);

                if (! empty($workflowStatuses)) {
                    $query->orWhereIn('to_status', $workflowStatuses)
                        ->orWhereIn('from_status', $workflowStatuses);
                }
            })
            ->latest();

        if ($canViewDrafts) {
            $agreements->orWhere('status', 'draft');
        }

        return $agreements->get();
    }

    private function canManageUsers(User $user): bool
    {
        $normalizedRole = AgreementWorkflowMap::normalizeRole($user->role ?? '');
        $systemAdminFallback = (
            ($user->name && str_contains(strtolower($user->name), 'system admin')) ||
            ($user->role && str_contains(strtolower($user->role), 'system'))
        );

        return in_array($normalizedRole, ['admin', 'system_admin'], true) || $systemAdminFallback;
    }

    private function recordActivity(string $action, ?Agreement $agreement = null, ?string $userName = null): void
    {
        ActivityLog::create([
            'user_name' => $userName ?? auth()->user()->name,
            'action' => $action,
            'agreement_title' => $agreement?->title ?? 'N/A',
        ]);
    }

    private function dashboardAnalytics(Collection $agreements): array
    {
        // Only approved agreements (active or renewed) should contribute to partner performance metrics
        $approvedAgreements = $agreements->whereIn('status', ['active', 'renewed'])->values();

        $renewalWindowEnd = now()->addDays(30);

        $expiringSoon = $agreements
            ->filter(function (Agreement $agreement) use ($renewalWindowEnd): bool {
                $expiresAt = $agreement->expires_at ? Carbon::parse($agreement->expires_at) : null;

                return $expiresAt
                    && $expiresAt->betweenIncluded(now(), $renewalWindowEnd);
            })
            ->sortBy('expires_at')
            ->values();

        $activePartnerships = $agreements->whereIn('status', ['active', 'renewed'])->count();
        $renewedAgreements = $agreements->where('status', 'renewed')->count();
        $expiredAgreements = $agreements->where('status', 'expired')->count();
        $renewalRate = $renewedAgreements + $expiredAgreements > 0
            ? round(($renewedAgreements / ($renewedAgreements + $expiredAgreements)) * 100, 1)
            : 0.0;

        $partnerPerformance = $approvedAgreements
            ->groupBy(function (Agreement $agreement): string {
                return trim($agreement->partner_organization) !== ''
                    ? trim($agreement->partner_organization)
                    : 'Unknown Institution';
            })
            ->map(function (Collection $partnerAgreements, string $partnerName) use ($renewalWindowEnd): array {
                $active = $partnerAgreements->whereIn('status', ['active', 'renewed'])->count();
                $renewed = $partnerAgreements->where('status', 'renewed')->count();
                $expired = $partnerAgreements->where('status', 'expired')->count();
                $expiringSoon = $partnerAgreements->filter(function (Agreement $agreement) use ($renewalWindowEnd): bool {
                    $expiresAt = $agreement->expires_at ? Carbon::parse($agreement->expires_at) : null;

                    return $expiresAt
                        && $expiresAt->betweenIncluded(now(), $renewalWindowEnd);
                })->count();

                $performanceScore = round(($active * 12) + ($renewed * 18) - ($expired * 8) - ($expiringSoon * 3), 1);
                $partnerRenewalRate = $renewed + $expired > 0
                    ? round(($renewed / ($renewed + $expired)) * 100, 1)
                    : 0.0;

                return [
                    'partner_organization' => $partnerName,
                    'total_partnerships' => $partnerAgreements->count(),
                    'active_partnerships' => $active,
                    'renewed_partnerships' => $renewed,
                    'expiring_soon' => $expiringSoon,
                    'renewal_rate' => $partnerRenewalRate,
                    'performance_score' => $performanceScore,
                ];
            })
            ->sortByDesc('performance_score')
            ->values()
            ->take(5)
            ->all();

        return [
            'activePartnerships' => $activePartnerships,
            'upcomingExpirations' => $expiringSoon->count(),
            'renewedAgreements' => $renewedAgreements,
            'renewalRate' => $renewalRate,
            // partnerCount should reflect unique partner organizations among approved agreements
            'partnerCount' => $approvedAgreements->pluck('partner_organization')->filter()->unique()->count(),
            'partnerPerformance' => $partnerPerformance,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD (ROLE BASED)
    |--------------------------------------------------------------------------
    */
    public function dashboard()
    {
        $user = auth()->user();

        // Auto-expire agreements
        Agreement::whereDate('expires_at', '<', now())
            ->whereNotIn('status', ['terminated', 'disabled'])
            ->update(['status' => 'expired']);

        Agreement::all()->each(function (Agreement $agreement): void {
            $originalStatus = $agreement->status;
            $this->syncAgreementStatus($agreement);

            if ($agreement->status !== $originalStatus) {
                $agreement->save();
            }
        });

        $agreements = Agreement::with('versions.uploadedBy')->latest()->get();
        $analytics = $this->dashboardAnalytics($agreements);

        $recentActivities = ActivityLog::latest()->take(10)->get();

        $notifications = Notification::where('user_id', $user->id)->latest()->take(10)->get();
        $unreadCount = Notification::where('user_id', $user->id)->where('is_read', false)->count();

        $expiringSoon = $agreements
            ->filter(function (Agreement $agreement): bool {
                $expiresAt = $agreement->expires_at ? Carbon::parse($agreement->expires_at) : null;

                return $expiresAt
                    && $expiresAt->betweenIncluded(now(), now()->addDays(30));
            })
            ->sortBy('expires_at')
            ->values();

        $expired = $agreements->where('status', 'expired')->values();

        $stats = [
            'total' => $agreements->count(),
            'draft' => $agreements->where('status', 'draft')->count(),
            'for_review' => $agreements->where('status', 'for_review')->count(),
            'active' => $agreements->where('status', 'active')->count(),
            'expired' => $agreements->where('status', 'expired')->count(),
            'renewed' => $agreements->where('status', 'renewed')->count(),
            'terminated' => $agreements->where('status', 'terminated')->count(),
            'disabled' => $agreements->where('status', 'disabled')->count(),
            'released' => $agreements->where('workflow_status', 'active_agreement')->count(),
            'pending' => $agreements->where('status', 'for_review')->count(),
        ];

        $workflowItems = $agreements
            ->filter(function (Agreement $agreement): bool {
                return $agreement->workflow_status !== null && $agreement->status !== 'draft';
            })
            ->take(10)
            ->values();

        $expiringSoonPreview = $expiringSoon
            ->map(function (Agreement $agreement): array {
                $expiresAt = $agreement->expires_at ? Carbon::parse($agreement->expires_at) : null;

                return [
                    'id' => $agreement->id,
                    'title' => $agreement->title,
                    'partner_organization' => $agreement->partner_organization,
                    'type' => $agreement->type,
                    'expires_at' => $expiresAt?->format('M j, Y'),
                    'workflow_status' => $agreement->workflow_status,
                ];
            })
            ->take(5)
            ->values();

        $normalizedRole = AgreementWorkflowMap::normalizeRole($user->role ?? '');

        switch ($normalizedRole) {
            case 'admin':
            case 'system_admin':
                return Inertia::render('AdminDashboard', [
                    'role' => $normalizedRole,
                    'stats' => $stats,
                    'analytics' => $analytics,
                    'expiringSoon' => $expiringSoon,
                    'expired' => $expired,
                    'recentActivities' => $recentActivities,
                ]);

            case 'coordinator':
                $stage = $user->coordinator_stage;

                return match ($stage) {
                    'legal_assistant_ii' => $this->stageCoordinatorDashboard($user, 'legal_assistant_ii'),
                    'legal_assistant_iii' => $this->stageCoordinatorDashboard($user, 'legal_assistant_iii'),
                    'attorney' => $this->attorneyDashboard($user),
                    'administrative_aid' => $this->stageCoordinatorDashboard($user, 'administrative_aid'),
                    'president_approval' => $this->stageCoordinatorDashboard($user, 'president_approval'),
                    default => $this->senderDashboard($user),
                };

            case 'authorized_personnel':
                return $this->authorizedPersonnelDashboard($user);

            default:
                return Inertia::render('RoleDashboard', [
                    'role' => $normalizedRole,
                    'roleTitle' => $this->roleDashboardTitle($normalizedRole),
                    'canCreateAgreement' => in_array($normalizedRole, ['authorized_personnel', 'admin'], true),
                    'stats' => $stats,
                    'analytics' => $analytics,
                    'submittedAgreements' => $normalizedRole === 'authorized_personnel'
                        ? $this->accessibleAgreementsForUser($user)
                        : collect(),
                    'assignedAgreements' => $this->roleAssignedAgreements($normalizedRole),
                    'workflowItems' => $workflowItems,
                    'finalApprovedAgreements' => Agreement::where('status', 'active')->latest()->get(),
                    'expiringSoon' => $expiringSoon,
                    'expired' => $expired,
                    'recentActivities' => $recentActivities,
                    // Add lightweight audit/version samples for visibility
                    'recentAudit' => WorkflowHistory::latest()->take(5)->get(),
                    'recentVersions' => AgreementVersion::with('uploadedBy')->latest()->take(5)->get(),
                    'expiringSoonPreview' => $expiringSoonPreview,
                    'notifications' => $notifications,
                    'unreadNotifications' => $unreadCount,
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STAGE-SPECIFIC COORDINATOR DASHBOARD
    |--------------------------------------------------------------------------
    */
    public function stageCoordinatorDashboard(User $user, string $stage)
    {
        $agreements = Agreement::with('versions.uploadedBy')->latest()->get();

        $agreements->each(function (Agreement $agreement): void {
            $originalStatus = $agreement->status;
            $this->syncAgreementStatus($agreement);

            if ($agreement->status !== $originalStatus) {
                $agreement->save();
            }
        });

        $analytics = $this->dashboardAnalytics($agreements);

        $agreementsAtStage = $agreements
            ->where('workflow_status', $stage)
            ->values();

        $allForReview = $agreements->where('status', 'for_review')->count();
        $activeCount = $agreements->whereIn('status', ['active', 'renewed'])->count();
        $expiredCount = $agreements->where('status', 'expired')->count();

        $stats = [
            'atStage' => $agreementsAtStage->count(),
            'allForReview' => $allForReview,
            'active' => $activeCount,
            'expired' => $expiredCount,
        ];

        $expiringSoon = $agreements
            ->filter(function (Agreement $agreement): bool {
                $expiresAt = $agreement->expires_at ? Carbon::parse($agreement->expires_at) : null;

                return $expiresAt && $expiresAt->betweenIncluded(now(), now()->addDays(30));
            })
            ->sortBy('expires_at')
            ->values();

        $expired = $agreements->where('status', 'expired')->values();

        $recentActivities = ActivityLog::latest()->take(10)->get();
        $notifications = Notification::where('user_id', $user->id)->latest()->take(10)->get();
        $unreadCount = Notification::where('user_id', $user->id)->where('is_read', false)->count();

        $nextStage = AgreementWorkflowMap::nextStatus($stage);
        $prevStage = AgreementWorkflowMap::previousStatusForReturn($stage);
        $stageName = AgreementWorkflowMap::humanizeStatus($stage) ?? $stage;
        $stageHandler = AgreementWorkflowMap::handlerForStatus($stage) ?? $stageName;

        $componentMap = [
            'legal_assistant_ii' => 'LegalIICoordinatorDashboard',
            'legal_assistant_iii' => 'LegalIIICoordinatorDashboard',
            'attorney' => 'AttorneyDashboard',
            'administrative_aid' => 'AdminAidDashboard',
            'president_approval' => 'PresidentDashboard',
        ];

        return Inertia::render($componentMap[$stage] ?? 'CoordinatorDashboard', [
            'stage' => $stage,
            'stageName' => $stageName,
            'stageHandler' => $stageHandler,
            'nextStage' => $nextStage,
            'prevStage' => $prevStage,
            'agreementsAtStage' => $agreementsAtStage,
            'stats' => $stats,
            'analytics' => $analytics,
            'expiringSoon' => $expiringSoon,
            'expired' => $expired,
            'recentActivities' => $recentActivities,
            'notifications' => $notifications,
            'unreadNotifications' => $unreadCount,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ATTORNEY DASHBOARD (Attorney handles 3 stages)
    |--------------------------------------------------------------------------
    */
    public function attorneyDashboard(User $user)
    {
        $agreements = Agreement::with('versions.uploadedBy')->latest()->get();

        $agreements->each(function (Agreement $agreement): void {
            $originalStatus = $agreement->status;
            $this->syncAgreementStatus($agreement);

            if ($agreement->status !== $originalStatus) {
                $agreement->save();
            }
        });

        $analytics = $this->dashboardAnalytics($agreements);

        $attorneyStages = ['attorney_review', 'attorney_initials'];
        $agreementsAtStage = $agreements
            ->whereIn('workflow_status', $attorneyStages)
            ->values();

        $stats = [
            'atStage' => $agreementsAtStage->count(),
            'inReview' => $agreements->where('workflow_status', 'attorney_review')->count(),
            'initials' => $agreements->where('workflow_status', 'attorney_initials')->count(),
            'allForReview' => $agreements->where('status', 'for_review')->count(),
            'active' => $agreements->whereIn('status', ['active', 'renewed'])->count(),
            'expired' => $agreements->where('status', 'expired')->count(),
        ];

        $expiringSoon = $agreements
            ->filter(function (Agreement $agreement): bool {
                $expiresAt = $agreement->expires_at ? Carbon::parse($agreement->expires_at) : null;

                return $expiresAt && $expiresAt->betweenIncluded(now(), now()->addDays(30));
            })
            ->sortBy('expires_at')
            ->values();

        $expired = $agreements->where('status', 'expired')->values();

        $recentActivities = ActivityLog::latest()->take(10)->get();
        $notifications = Notification::where('user_id', $user->id)->latest()->take(10)->get();
        $unreadCount = Notification::where('user_id', $user->id)->where('is_read', false)->count();

        $nextStage = 'administrative_aid';
        $prevStage = 'legal_assistant_iii';
        $stageName = 'Attorney';
        $stageHandler = 'Attorney';

        return Inertia::render('AttorneyDashboard', [
            'stage' => 'attorney',
            'stageName' => $stageName,
            'stageHandler' => $stageHandler,
            'nextStage' => $nextStage,
            'prevStage' => $prevStage,
            'agreementsAtStage' => $agreementsAtStage,
            'stats' => $stats,
            'analytics' => $analytics,
            'expiringSoon' => $expiringSoon,
            'expired' => $expired,
            'recentActivities' => $recentActivities,
            'notifications' => $notifications,
            'unreadNotifications' => $unreadCount,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHORIZED PERSONNEL DASHBOARD
    |--------------------------------------------------------------------------
    */
    public function authorizedPersonnelDashboard(User $user)
    {
        $agreements = Agreement::with('versions.uploadedBy')->latest()->get();

        $agreements->each(function (Agreement $agreement): void {
            $originalStatus = $agreement->status;
            $this->syncAgreementStatus($agreement);

            if ($agreement->status !== $originalStatus) {
                $agreement->save();
            }
        });

        $analytics = $this->dashboardAnalytics($agreements);

        $myAgreements = $agreements->where('submitted_by', $user->id);

        $inReview = $myAgreements->whereIn('workflow_status', [
            'legal_assistant_ii',
            'legal_assistant_iii',
            'attorney_review',
            'administrative_aid',
            'attorney_initials',
            'president_approval',
        ])->values();
        $active = $myAgreements->whereIn('status', ['active', 'renewed'])->values();
        $drafts = $myAgreements->where('status', 'draft')->values();

        $stats = [
            'total' => $myAgreements->count(),
            'drafts' => $drafts->count(),
            'inReview' => $inReview->count(),
            'active' => $active->count(),
            'expired' => $myAgreements->where('status', 'expired')->count(),
        ];

        $expiringSoon = $myAgreements
            ->filter(function (Agreement $agreement): bool {
                $expiresAt = $agreement->expires_at ? Carbon::parse($agreement->expires_at) : null;

                return $expiresAt && $expiresAt->betweenIncluded(now(), now()->addDays(30));
            })
            ->sortBy('expires_at')
            ->values();

        $expired = $myAgreements->where('status', 'expired')->values();

        $recentActivities = ActivityLog::where('user_name', $user->name)->latest()->take(10)->get();
        $notifications = Notification::where('user_id', $user->id)->latest()->take(10)->get();
        $unreadCount = Notification::where('user_id', $user->id)->where('is_read', false)->count();

        return Inertia::render('AuthorizedPersonnelDashboard', [
            'myAgreements' => $myAgreements,
            'drafts' => $drafts,
            'inReview' => $inReview,
            'active' => $active,
            'stats' => $stats,
            'analytics' => $analytics,
            'expiringSoon' => $expiringSoon,
            'expired' => $expired,
            'recentActivities' => $recentActivities,
            'notifications' => $notifications,
            'unreadNotifications' => $unreadCount,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SENDER DASHBOARD (Non-stage coordinator)
    |--------------------------------------------------------------------------
    */
    public function senderDashboard(User $user)
    {
        $agreements = Agreement::with('versions.uploadedBy')->latest()->get();

        $agreements->each(function (Agreement $agreement): void {
            $originalStatus = $agreement->status;
            $this->syncAgreementStatus($agreement);

            if ($agreement->status !== $originalStatus) {
                $agreement->save();
            }
        });

        $analytics = $this->dashboardAnalytics($agreements);

        $myAgreements = $agreements->where('submitted_by', $user->id);
        $drafts = $myAgreements->where('status', 'draft')->values();
        $submitted = $myAgreements->where('status', 'for_review')->values();
        $active = $myAgreements->whereIn('status', ['active', 'renewed'])->values();

        $stats = [
            'total' => $myAgreements->count(),
            'drafts' => $drafts->count(),
            'submitted' => $submitted->count(),
            'active' => $active->count(),
            'expired' => $myAgreements->where('status', 'expired')->count(),
        ];

        $expiringSoon = $agreements
            ->filter(function (Agreement $agreement) use ($user): bool {
                if ($agreement->submitted_by !== $user->id) {
                    return false;
                }
                $expiresAt = $agreement->expires_at ? Carbon::parse($agreement->expires_at) : null;

                return $expiresAt && $expiresAt->betweenIncluded(now(), now()->addDays(30));
            })
            ->sortBy('expires_at')
            ->values();

        $expired = $myAgreements->where('status', 'expired')->values();

        $recentActivities = ActivityLog::where('user_name', $user->name)->latest()->take(10)->get();
        $notifications = Notification::where('user_id', $user->id)->latest()->take(10)->get();
        $unreadCount = Notification::where('user_id', $user->id)->where('is_read', false)->count();

        return Inertia::render('SenderDashboard', [
            'myAgreements' => $myAgreements,
            'drafts' => $drafts,
            'submitted' => $submitted,
            'active' => $active,
            'stats' => $stats,
            'analytics' => $analytics,
            'expiringSoon' => $expiringSoon,
            'expired' => $expired,
            'recentActivities' => $recentActivities,
            'notifications' => $notifications,
            'unreadNotifications' => $unreadCount,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AGREEMENTS LIST
    |--------------------------------------------------------------------------
    */
    public function agreements()
    {
        $user = auth()->user();
        $normalizedRole = AgreementWorkflowMap::normalizeRole($user->role ?? '');
        $filter = request()->query('filter');

        // Viewers should not access the agreements management list
        if ($normalizedRole === 'viewer') {
            abort(403);
        }

        Agreement::all()->each(function (Agreement $agreement): void {
            $originalStatus = $agreement->status;
            $this->syncAgreementStatus($agreement);

            if ($agreement->status !== $originalStatus) {
                $agreement->save();
            }
        });

        if ($filter === 'drafts') {
            if (in_array($normalizedRole, ['admin', 'coordinator'], true)) {
                $agreements = Agreement::with(['versions.uploadedBy', 'workflowHistories'])
                    ->where('status', 'draft')
                    ->latest()
                    ->get();
            } elseif ($this->canViewDraftAgreements($normalizedRole)) {
                $agreements = $this->draftAgreementsForUser($user);
            } else {
                abort(403);
            }

            $agreements = $this->decorateAgreementAccessContext($agreements, $user);

            return Inertia::render('Agreements', [
                'agreements' => $agreements,
            ]);
        }

        if (in_array($normalizedRole, ['admin', 'coordinator'], true)) {
            // Admins and coordinators see approved agreements only (active or renewed)
            // Eager-load workflow + version relations so the frontend can display consistent metadata
            $agreements = Agreement::with(['versions.uploadedBy', 'workflowHistories'])
                ->whereIn('status', ['active', 'renewed'])
                ->latest()
                ->get();
        } else {
            // Non-admins see approved agreements they can access
            $agreements = $this->accessibleAgreementsForUser($user)
                ->whereIn('status', ['active', 'renewed'])
                ->values();
        }

        $agreements = $this->decorateAgreementAccessContext($agreements, $user);

        return Inertia::render('Agreements', [
            'agreements' => $agreements,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PAGE
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $user = auth()->user();

        $normalized = AgreementWorkflowMap::normalizeRole($user->role ?? '');

        if (! in_array(AgreementWorkflowMap::normalizeRole($normalized), [
            'authorized_personnel',
            'legal_assistant_ii',
            'legal_assistant_iii',
            'attorney',
            'administrative_aid',
            'coordinator',
            'admin',
            'president',
        ], true)) {
            abort(403);
        }

        return Inertia::render('AddAgreement');
    }

    public function edit($id)
    {
        $agreement = Agreement::with('versions.uploadedBy', 'workflowHistories')->findOrFail($id);
        $this->authorize('update', $agreement);

        return Inertia::render('EditAgreement', [
            'agreement' => $agreement,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE (AUTHORIZED PERSONNEL STARTS WORKFLOW)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $currentNormalized = AgreementWorkflowMap::normalizeRole(auth()->user()->role ?? '');
        $allowedCreators = [
            'authorized_personnel',
            'legal_assistant_ii',
            'legal_assistant_iii',
            'attorney',
            'administrative_aid',
            'coordinator',
            'admin',
            'president',
        ];

        if (! in_array($currentNormalized, $allowedCreators, true)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['MOA', 'MOU'])],
            'partner_organization' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'signed_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:signed_at'],
            'document' => ['nullable', 'file', 'mimes:pdf'],
        ]);

        $uploadedDocument = $request->file('document');
        unset($validated['document']);

        // If the client requested a draft, save as draft without starting workflow
        $incomingStatus = $request->input('status', '') ?: 'for_review';

        if ($incomingStatus === 'draft') {
            $validated['status'] = 'draft';
            $validated['submitted_by'] = auth()->id();

            $agreement = Agreement::create($validated);

            if ($uploadedDocument) {
                $agreement->document = $this->documentStorageService->storeCurrentDocument($agreement, $uploadedDocument);
                $agreement->save();

                $this->documentStorageService->storeVersionSnapshot($agreement, $agreement->document, auth()->id(), auth()->user()->name);
            }

            // Do not start workflow, do not create workflow history or submitted activity for drafts
            $this->recordActivity('Saved Draft Agreement', $agreement);

            return redirect('/agreements');
        }

        if ($currentNormalized === 'president') {
            $validated['status'] = 'active';
            $validated['workflow_status'] = 'active_agreement';
            $validated['current_handler'] = 'Authorized Personnel';
            $validated['submitted_by'] = auth()->id();

            $agreement = Agreement::create($validated);

            if ($uploadedDocument) {
                $agreement->document = $this->documentStorageService->storeCurrentDocument($agreement, $uploadedDocument);
                $agreement->save();

                $this->documentStorageService->storeVersionSnapshot($agreement, $agreement->document, auth()->id(), auth()->user()->name);
            }

            WorkflowHistory::create([
                'agreement_id' => $agreement->id,
                'action' => 'Approved',
                'performed_by' => auth()->user()->name,
                'from_status' => 'president_approval',
                'to_status' => 'active_agreement',
                'remarks' => 'Final approval by president',
            ]);

            $this->recordActivity('Approved Agreement', $agreement);

            return redirect('/agreements');
        }

        // START WORKFLOW for non-draft submissions
        $validated['status'] = 'for_review';
        $validated['submitted_by'] = auth()->id();

        $user = auth()->user();
        $targetWorkflowStatus = 'legal_assistant_ii';
        $targetHandler = 'Legal Assistant II';

        if ($user->isCoordinator() && $user->coordinator_stage !== null) {
            $targetWorkflowStatus = AgreementWorkflowMap::nextStatus($user->coordinator_stage);
            if (! $targetWorkflowStatus) {
                $targetWorkflowStatus = 'legal_assistant_ii';
            }
            $targetHandler = AgreementWorkflowMap::handlerForStatus($targetWorkflowStatus);
        }

        $validated['workflow_status'] = $targetWorkflowStatus;
        $validated['current_handler'] = $targetHandler;

        $agreement = Agreement::create($validated);

        if ($uploadedDocument) {
            $agreement->document = $this->documentStorageService->storeCurrentDocument($agreement, $uploadedDocument);
            $agreement->save();

            $this->documentStorageService->storeVersionSnapshot($agreement, $agreement->document, auth()->id(), auth()->user()->name);
        }

        $originalStatus = $agreement->status;
        $this->syncAgreementStatus($agreement);

        if ($agreement->status !== $originalStatus) {
            $agreement->save();
        }

        WorkflowHistory::create([
            'agreement_id' => $agreement->id,
            'action' => 'Submitted',
            'performed_by' => auth()->user()->name,
            'from_status' => 'authorized_personnel',
            'to_status' => $targetWorkflowStatus,
            'remarks' => 'Initial submission',
        ]);

        // Notify appropriate stage users about the new submission
        try {
            $submitter = auth()->user()->name;
            $agreementType = $agreement->type ?? 'Agreement';
            $humanTarget = AgreementWorkflowMap::humanizeStatus($targetWorkflowStatus) ?? $targetWorkflowStatus;
            $title = 'New Submission';
            $message = "{$submitter} submitted a {$agreementType} titled '{$agreement->title}' to {$humanTarget} for review.";

            $targetRole = $this->roleTokenForWorkflowStatus($targetWorkflowStatus) ?? $targetWorkflowStatus;
            $targetUsers = User::whereIn(DB::raw("LOWER(REPLACE(role, ' ', '_'))"), AgreementWorkflowMap::aliasesForRole($targetRole))->get();
            foreach ($targetUsers as $target) {
                Notification::create([
                    'title' => $title,
                    'message' => $message,
                    'is_read' => false,
                    'user_id' => $target->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to notify on submission', ['error' => $e->getMessage()]);
        }

        $this->recordActivity('Submitted Agreement', $agreement);

        return redirect('/agreements');
    }

    public function update(Request $request, $id)
    {
        $agreement = Agreement::findOrFail($id);
        $this->authorize('update', $agreement);

        // When only updating status/document from workflow, make other fields optional
        $isWorkflowUpdate = $request->has('status') && ! $request->has('title');

        $validated = $request->validate([
            'title' => [$isWorkflowUpdate ? 'nullable' : 'required', 'string', 'max:255'],
            'type' => [$isWorkflowUpdate ? 'nullable' : 'required', Rule::in(['MOA', 'MOU'])],
            'partner_organization' => [$isWorkflowUpdate ? 'nullable' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'signed_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:signed_at'],
            'status' => ['nullable', Rule::in(['draft', 'for_review', 'active', 'expired', 'renewed', 'terminated', 'disabled'])],
            'document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $uploadedDocument = $request->file('document');
        unset($validated['document']);

        if ($uploadedDocument) {
            $validated['document'] = $this->documentStorageService->storeCurrentDocument($agreement, $uploadedDocument);
            $this->documentStorageService->storeVersionSnapshot($agreement, $validated['document'], auth()->id(), auth()->user()->name);
        }

        $previousStatus = $agreement->status;
        $previousWorkflowStatus = $agreement->workflow_status;

        $agreement->fill($validated);

        if ($previousStatus === 'draft' && $validated['status'] === 'for_review') {
            $agreement->workflow_status = 'legal_assistant_ii';
            $agreement->current_handler = 'Legal Assistant II';
        }

        $this->syncAgreementStatus($agreement);

        if ($previousStatus === 'expired' && $agreement->status === 'active') {
            $agreement->status = 'renewed';
        }

        $agreement->save();

        if ($request->hasFile('document')) {
            $this->recordActivity('Uploaded Revised Agreement', $agreement);
        }

        if ($previousStatus === 'draft' && $validated['status'] === 'for_review') {
            WorkflowHistory::create([
                'agreement_id' => $agreement->id,
                'action' => 'Submitted',
                'performed_by' => auth()->user()->name,
                'from_status' => 'draft',
                'to_status' => 'legal_assistant_ii',
                'remarks' => 'Submitted draft for Legal Assistant II review',
            ]);

            try {
                $submitter = auth()->user()->name;
                $agreementType = $agreement->type ?? 'Agreement';
                $title = 'Draft Submitted';
                $message = "{$submitter} submitted a draft {$agreementType} titled '{$agreement->title}' to legal_assistant_ii for review.";

                $targetRole = $this->roleTokenForWorkflowStatus('legal_assistant_ii') ?? 'legal_assistant_ii';
                $targetUsers = User::whereIn(DB::raw("LOWER(REPLACE(role, ' ', '_'))"), AgreementWorkflowMap::aliasesForRole($targetRole))->get();
                foreach ($targetUsers as $target) {
                    Notification::create([
                        'title' => $title,
                        'message' => $message,
                        'is_read' => false,
                        'user_id' => $target->id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to notify on draft submission', ['error' => $e->getMessage()]);
            }

            $this->recordActivity('Submitted Draft Agreement', $agreement);
        }

        $this->recordActivity('Updated Agreement', $agreement);

        return redirect('/agreements/'.$agreement->id);
    }

    /**
     * Mark a notification as read for the authenticated user.
     */
    public function markNotificationRead($id)
    {
        $notification = Notification::findOrFail($id);

        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->is_read = true;
        $notification->save();

        return response()->json(['ok' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | WORKFLOW FORWARDING (MAIN FLOW ENGINE)
    |--------------------------------------------------------------------------
    */
    public function forwardWorkflow(Request $request, $id)
    {
        // Log incoming forward attempts for debugging 404/authorization issues
        try {
            Log::info('forwardWorkflow attempt', [
                'user_id' => auth()->id(),
                'user_role' => auth()->user()?->role,
                'agreement_id' => $id,
                'payload' => $request->all(),
            ]);
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        $agreement = Agreement::findOrFail($id);
        $this->authorize('forwardWorkflow', [$agreement, $request->next_status]);

        $result = $this->workflowService->forward(
            $agreement,
            auth()->user(),
            $request->next_status,
            $request->remarks ?? null,
            $request->input('version_id'),
            $request->input('target_user_id') ? (int) $request->input('target_user_id') : null
        );

        $this->recordActivity(
            "Moved {$result['from']} → {$result['to']}".(! empty($result['version_id']) ? " (version {$result['version_id']})" : ''),
            $agreement
        );

        return back();
    }

    public function disable($id)
    {
        $agreement = Agreement::findOrFail($id);
        $this->authorize('disable', $agreement);

        $this->workflowService->disable($agreement);

        $this->recordActivity('Disabled Agreement', $agreement);

        return back();
    }

    /*
    |--------------------------------------------------------------------------
    | RETURN FLOW (SEND BACKWARD)
    |--------------------------------------------------------------------------
    */
    public function returnAgreement(Request $request, $id)
    {
        $agreement = Agreement::findOrFail($id);
        $this->authorize('returnAgreement', $agreement);

        $result = $this->workflowService->returnWorkflow($agreement, auth()->user(), $request->remarks, $request->input('return_to'));

        $this->recordActivity("Returned Agreement {$result['from']} → {$result['to']}", $agreement);

        return back();
    }

    /*
    |--------------------------------------------------------------------------
    | DETAILS
    |--------------------------------------------------------------------------
    */
    private function resolvedWorkflowStatus(Agreement $agreement): ?string
    {
        if ($agreement->workflow_status) {
            return $agreement->workflow_status;
        }

        $latestHistory = $agreement->workflowHistories()->latest('id')->first();

        return $latestHistory?->to_status;
    }

    private function nextStageUsers(Agreement $agreement)
    {
        $currentStatus = $this->resolvedWorkflowStatus($agreement);
        $nextStatus = $this->nextWorkflowStatus($currentStatus);

        if (! $nextStatus) {
            return collect([]);
        }

        $targetRole = AgreementWorkflowMap::roleForStatus($nextStatus);

        if (! $targetRole) {
            return collect([]);
        }

        return User::whereIn(DB::raw('LOWER(REPLACE(role, " ", "_"))'), AgreementWorkflowMap::aliasesForRole($targetRole))->get();
    }

    private function nextWorkflowStatus(?string $workflowStatus): ?string
    {
        return match ($workflowStatus) {
            'legal_assistant_ii' => 'legal_assistant_iii',
            'legal_assistant_iii' => 'attorney_review',
            'attorney_review' => 'administrative_aid',
            'administrative_aid' => 'attorney_initials',
            'attorney_initials' => 'president_approval',
            'president_approval' => 'active_agreement',
            default => null,
        };
    }

    public function show($id)
    {
        $agreement = Agreement::with('versions', 'workflowHistories')->findOrFail($id);
        $this->authorize('view', $agreement);

        $originalStatus = $agreement->status;
        $this->syncAgreementStatus($agreement);

        if ($agreement->status !== $originalStatus) {
            $agreement->save();
        }

        $isSubscribed = false;

        try {
            $isSubscribed = auth()->check() && $agreement->subscriptions()->where('user_id', auth()->id())->exists();
        } catch (\Throwable $e) {
            $isSubscribed = false;
        }

        $currentWorkflowStatus = $this->resolvedWorkflowStatus($agreement);
        $nextWorkflowStatus = $this->nextWorkflowStatus($currentWorkflowStatus);
        $currentWorkflowRole = AgreementWorkflowMap::roleForStatus($currentWorkflowStatus);
        $nextWorkflowRole = AgreementWorkflowMap::roleForStatus($nextWorkflowStatus);

        $partnerUsers = $this->nextStageUsers($agreement)->map(function (User $u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'role' => $u->role,
                'role_normalized' => strtolower(str_replace(' ', '_', $u->role ?? '')),
            ];
        })->values();

        return Inertia::render('AgreementDetails', [
            'agreement' => $agreement->setAttribute('isSubscribed', $isSubscribed),
            'versions' => $agreement->versions()->with('uploadedBy')->latest()->get(),
            'workflowHistory' => $agreement->workflowHistories()->latest()->get(),
            'partnerUsers' => $partnerUsers,
            'currentWorkflowStatus' => $currentWorkflowStatus,
            'currentWorkflowRole' => $currentWorkflowRole,
            'nextWorkflowStatus' => $nextWorkflowStatus,
            'nextWorkflowRole' => $nextWorkflowRole,
        ]);
    }

    public function download($id)
    {
        $agreement = Agreement::findOrFail($id);
        $this->authorize('view', $agreement);

        if (! $agreement->document || ! $agreement->document) {
            abort(404, 'Document not found');
        }

        $latestVersion = $agreement->versions()->max('version');
        $filename = $this->generateDownloadFilename($agreement, $latestVersion);

        return Storage::disk('public')->download($agreement->document, $filename);
    }

    public function view($id)
    {
        $agreement = Agreement::findOrFail($id);
        $this->authorize('view', $agreement);

        if (! $agreement->document) {
            abort(404, 'Document not found');
        }

        $path = Storage::disk('public')->path($agreement->document);
        $mime = Storage::disk('public')->mimeType($agreement->document) ?? 'application/pdf';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($agreement->document).'"',
        ]);
    }

    public function downloadVersion($id, $versionId)
    {
        $agreement = Agreement::findOrFail($id);
        $this->authorize('view', $agreement);

        $version = $agreement->versions()->findOrFail($versionId);

        if (! $version->document) {
            abort(404, 'Version document not found');
        }

        $filename = $this->generateDownloadFilename($agreement, $version->version);

        return Storage::disk('public')->download($version->document, $filename);
    }

    public function viewVersion($id, $versionId)
    {
        $agreement = Agreement::findOrFail($id);
        $this->authorize('view', $agreement);

        $version = $agreement->versions()->findOrFail($versionId);

        if (! $version->document) {
            abort(404, 'Version document not found');
        }

        $path = Storage::disk('public')->path($version->document);
        $mime = Storage::disk('public')->mimeType($version->document) ?? 'application/pdf';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($version->document).'"',
        ]);
    }

    private function generateDownloadFilename(Agreement $agreement, ?string $version = null): string
    {
        $type = strtoupper($agreement->type ?? 'AGREEMENT');
        $title = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $agreement->title ?? 'agreement');
        $cleanVersion = $version !== null ? ltrim($version, 'v') : null;
        $versionSuffix = $cleanVersion ? "-v{$cleanVersion}" : '';

        return "{$type}-{$title}{$versionSuffix}.pdf";
    }

    /*
    |--------------------------------------------------------------------------
    | WORKFLOW DASHBOARD (TRACK ALL STAGES)
    |--------------------------------------------------------------------------
    */
    public function coordinatorWorkflowDashboard()
    {
        $payload = [];

        foreach (AgreementWorkflowMap::coordinatorDashboardQueries() as $key => $queryConfig) {
            $payload[$key] = Agreement::where($queryConfig['column'], $queryConfig['value'])->get();
        }

        return Inertia::render('CoordinatorWorkflowDashboard', $payload);
    }

    public function users()
    {
        $current = auth()->user();

        if (! $this->canManageUsers($current)) {
            abort(403);
        }

        return Inertia::render('Users', [
            'users' => User::latest()->get(),
        ]);
    }

    /**
     * Show agreements related to a specific user (submitted or uploaded).
     */
    public function userAgreements($id)
    {
        $viewer = auth()->user();

        $target = User::findOrFail($id);

        // Allow viewing if the viewer is admin/system_admin or the user themselves
        $viewerNormalized = strtolower(str_replace(' ', '_', $viewer->role ?? ''));
        $isAdmin = in_array($viewerNormalized, ['admin', 'system_admin'], true);

        if (! $isAdmin && $viewer->id !== $target->id) {
            abort(403);
        }

        // Get agreements the target submitted, uploaded versions for, or assigned to their role
        $agreements = $this->accessibleAgreementsForUser($target)->load(['versions.uploadedBy', 'workflowHistories']);

        $agreements = $agreements->map(function (Agreement $agreement) use ($target) {
            $latestVersion = $agreement->versions->sortByDesc('id')->first();
            $latestWorkflowHistory = $agreement->workflowHistories->sortByDesc('id')->first();
            $relationLabel = null;
            $receivedFrom = null;
            $strictUploaderIdentity = $this->useStrictUploaderIdentity();

            if ($agreement->submitted_by === $target->id) {
                $relationLabel = 'Submitted by this user';
            } elseif ($latestVersion && (
                $latestVersion->uploaded_by_id === $target->id ||
                (! $strictUploaderIdentity && $latestVersion->uploaded_by === $target->name)
            )) {
                $relationLabel = 'Uploaded by this user';
            } elseif (! empty($agreement->workflow_status)) {
                $relationLabel = 'Received';
                $receivedFrom = $latestWorkflowHistory?->performed_by;
            }

            $agreement->setAttribute('relation_label', $relationLabel);
            $agreement->setAttribute('received_from', $receivedFrom);

            return $agreement;
        });

        return Inertia::render('UserAgreements', [
            'user' => $target,
            'agreements' => $agreements,
        ]);
    }

    public function createUser()
    {
        $current = auth()->user();

        if (! $this->canManageUsers($current)) {
            abort(403);
        }

        return Inertia::render('AddUser');
    }

    public function storeUser(Request $request)
    {
        $current = auth()->user();

        if (! $this->canManageUsers($current)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'role' => ['required', 'string', 'in:admin,coordinator,authorized_personnel'],
            'coordinator_stage' => ['nullable', 'string', 'in:legal_assistant_ii,legal_assistant_iii,attorney,administrative_aid,president_approval'],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'status' => 'active',
            'role' => $validated['role'],
        ];

        if ($validated['role'] === 'coordinator' && isset($validated['coordinator_stage'])) {
            $userData['coordinator_stage'] = $validated['coordinator_stage'];
        }

        User::create($userData);

        $this->recordActivity('Created User: '.$validated['name'], null, auth()->user()->name);

        return redirect('/users');
    }

    public function disableUser($id)
    {
        $current = auth()->user();

        // Only admin or system_admin may disable users
        if (! $this->canManageUsers($current)) {
            abort(403);
        }

        $user = User::findOrFail($id);
        $user->status = 'disabled';
        $user->save();

        $this->recordActivity('Disabled User: '.$user->name, null, auth()->user()->name);

        return back();
    }

    public function activityLogs()
    {
        return Inertia::render('ActivityLogs', [
            'logs' => ActivityLog::latest()->get(),
        ]);
    }
    // Administrative destructive actions removed: clearActivityLogs() and resetAgreements()
}
