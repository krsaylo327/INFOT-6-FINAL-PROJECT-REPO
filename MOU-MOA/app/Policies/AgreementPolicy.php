<?php

namespace App\Policies;

use App\Models\Agreement;
use App\Models\User;
use App\Support\AgreementWorkflowMap;

class AgreementPolicy
{
    private function normalizedRole(User $user): string
    {
        return AgreementWorkflowMap::normalizeRole(strtolower(str_replace(' ', '_', $user->role ?? '')));
    }

    private function canAccessDraftAgreement(User $user, Agreement $agreement): bool
    {
        if ($agreement->submitted_by === $user->id) {
            return true;
        }

        return $agreement->versions()->where('uploaded_by_id', $user->id)->exists();
    }

    public function view(User $user, Agreement $agreement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCoordinator()) {
            return true;
        }

        if ($user->isAuthorizedPersonnel()) {
            return $agreement->partner_organization_id === $user->organization_id
                || $user->id === $agreement->submitted_by;
        }

        if ($agreement->status === 'draft') {
            return $this->canAccessDraftAgreement($user, $agreement);
        }

        return $user->id === $agreement->submitted_by;
    }

    public function create(User $user): bool
    {
        return $user->isCoordinator();
    }

    public function update(User $user, Agreement $agreement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCoordinator() && AgreementWorkflowMap::canCoordinatorActAtStage($user, $agreement->workflow_status)) {
            return true;
        }

        if ($user->id === $agreement->submitted_by && $agreement->status === 'draft') {
            return true;
        }

        return $agreement->versions()->where('uploaded_by_id', $user->id)->exists();
    }

    public function disable(User $user, Agreement $agreement): bool
    {
        return $user->isAdmin() || $user->id === $agreement->submitted_by;
    }

    public function forwardWorkflow(User $user, Agreement $agreement, ?string $targetStatus = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isCoordinator()) {
            return false;
        }

        $currentStage = $agreement->workflow_status;

        if (! AgreementWorkflowMap::canCoordinatorActAtStage($user, $currentStage)) {
            return false;
        }

        if ($targetStatus !== null) {
            $validNext = AgreementWorkflowMap::nextStatus($currentStage);
            if ($targetStatus !== $validNext) {
                return false;
            }
        }

        return true;
    }

    public function returnAgreement(User $user, Agreement $agreement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCoordinator()) {
            return true;
        }

        return false;
    }
}
