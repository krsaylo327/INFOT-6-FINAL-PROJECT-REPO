<?php

namespace App\Support;

use App\Models\User;

final class AgreementWorkflowMap
{
    private const WORKFLOW_STAGES = [
        'legal_assistant_ii',
        'legal_assistant_iii',
        'attorney_review',
        'administrative_aid',
        'attorney_initials',
        'president_approval',
    ];

    private const STATUS_ROLE = [
        'legal_assistant_ii' => 'coordinator',
        'legal_assistant_iii' => 'coordinator',
        'attorney_review' => 'coordinator',
        'administrative_aid' => 'coordinator',
        'attorney_initials' => 'coordinator',
        'president_approval' => 'coordinator',
        'active_agreement' => null,
    ];

    private const STATUS_HANDLER = [
        'legal_assistant_ii' => 'Legal Assistant II',
        'legal_assistant_iii' => 'Legal Assistant III',
        'attorney_review' => 'Attorney',
        'administrative_aid' => 'Administrative Aid',
        'attorney_initials' => 'Attorney',
        'president_approval' => 'President',
        'active_agreement' => 'Active',
    ];

    private const BACKFLOW = [
        'legal_assistant_iii' => 'legal_assistant_ii',
        'attorney_review' => 'legal_assistant_iii',
        'administrative_aid' => 'attorney_review',
        'attorney_initials' => 'administrative_aid',
        'president_approval' => 'attorney_initials',
        'active_agreement' => 'president_approval',
    ];

    private const FORWARD_TO_ATTORNEY_STAGES = [
        'attorney_review' => 'legal_assistant_iii',
        'attorney_initials' => 'administrative_aid',
    ];

    private const WORKFLOW_TO_REVIEW_STAGES = [
        'legal_assistant_ii',
        'legal_assistant_iii',
        'attorney_review',
        'administrative_aid',
        'attorney_initials',
        'president_approval',
    ];

    private const RETURN_ALLOWED_ROLES = [
        'coordinator',
        'admin',
    ];

    private const COORDINATOR_DASHBOARD_QUERIES = [
        'legalAssistantII' => ['column' => 'workflow_status', 'value' => 'legal_assistant_ii'],
        'legalAssistantIII' => ['column' => 'workflow_status', 'value' => 'legal_assistant_iii'],
        'attorneyReview' => ['column' => 'workflow_status', 'value' => 'attorney_review'],
        'adminLogging' => ['column' => 'workflow_status', 'value' => 'administrative_aid'],
        'attorneyInitials' => ['column' => 'workflow_status', 'value' => 'attorney_initials'],
        'presidentApproval' => ['column' => 'workflow_status', 'value' => 'president_approval'],
        'activeAgreements' => ['column' => 'status', 'value' => 'active'],
    ];

    private const ATTORNEY_STAGES = [
        'attorney_review',
        'attorney_initials',
    ];

    public static function allStages(): array
    {
        return self::WORKFLOW_STAGES;
    }

    public static function isAttorneyStage(string $stage): bool
    {
        return in_array($stage, self::ATTORNEY_STAGES, true);
    }

    public static function normalizeRole(string $role): string
    {
        $normalized = strtolower(str_replace(' ', '_', trim($role)));

        if ($normalized === 'system_admin') {
            return 'admin';
        }

        if ($normalized === 'college_personnel') {
            return 'coordinator';
        }

        return $normalized;
    }

    public static function aliasesForRole(string $role): array
    {
        $normalized = self::normalizeRole($role);

        return match ($normalized) {
            'authorized_personnel' => ['authorized_personnel'],
            'admin' => ['admin', 'system_admin'],
            'system_admin' => ['admin', 'system_admin'],
            default => [$normalized],
        };
    }

    public static function roleForStatus(?string $workflowStatus): ?string
    {
        if (! $workflowStatus) {
            return null;
        }

        return self::STATUS_ROLE[$workflowStatus] ?? null;
    }

    public static function handlerForStatus(string $workflowStatus): string
    {
        return self::STATUS_HANDLER[$workflowStatus] ?? 'Unknown';
    }

    public static function previousStatusForReturn(?string $fromStatus): string
    {
        if (! $fromStatus) {
            return 'legal_assistant_ii';
        }

        return self::BACKFLOW[$fromStatus] ?? 'legal_assistant_ii';
    }

    public static function reviewStages(): array
    {
        return self::WORKFLOW_TO_REVIEW_STAGES;
    }

    public static function canRoleReturn(string $normalizedRole): bool
    {
        return in_array($normalizedRole, self::RETURN_ALLOWED_ROLES, true);
    }

    public static function humanizeStatus(?string $workflowStatus): ?string
    {
        if (! $workflowStatus) {
            return null;
        }

        return str($workflowStatus)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public static function coordinatorDashboardQueries(): array
    {
        return self::COORDINATOR_DASHBOARD_QUERIES;
    }

    public static function canCoordinatorActAtStage(User $user, string $stage): bool
    {
        if (! $user->isCoordinator()) {
            return false;
        }

        if ($user->coordinator_stage === null) {
            return false;
        }

        if ($user->coordinator_stage === $stage) {
            return true;
        }

        if ($user->coordinator_stage === 'attorney' && self::isAttorneyStage($stage)) {
            return true;
        }

        return false;
    }

    public static function nextStatus(string $currentStatus): ?string
    {
        $stages = array_merge(self::WORKFLOW_STAGES, ['active_agreement']);
        $currentIndex = array_search($currentStatus, $stages, true);

        if ($currentIndex === false || $currentIndex >= count($stages) - 1) {
            return null;
        }

        return $stages[$currentIndex + 1];
    }

    public static function requiredStageForForwardTo(string $targetStage): ?string
    {
        return self::FORWARD_TO_ATTORNEY_STAGES[$targetStage] ?? null;
    }

    public static function canCoordinatorForwardTo(User $user, string $targetStage): bool
    {
        if (! $user->isCoordinator()) {
            return false;
        }

        $requiredStage = self::requiredStageForForwardTo($targetStage);

        if ($requiredStage === null) {
            return true;
        }

        return $user->coordinator_stage === $requiredStage;
    }

    public static function statusesForRole(string $normalizedRole): array
    {
        return match ($normalizedRole) {
            'admin' => self::WORKFLOW_STAGES,
            'coordinator' => self::WORKFLOW_STAGES,
            default => [],
        };
    }
}
