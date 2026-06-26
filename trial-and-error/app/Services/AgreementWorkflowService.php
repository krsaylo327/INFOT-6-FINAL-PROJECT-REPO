<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Notification;
use App\Models\User;
use App\Models\WorkflowHistory;
use App\Support\AgreementWorkflowMap;
use Illuminate\Support\Collection;

class AgreementWorkflowService
{
    public function forward(Agreement $agreement, User $actor, string $nextStatus, ?string $remarks = null, ?string $versionId = null, ?int $targetUserId = null): array
    {
        $from = $agreement->workflow_status;

        $validNext = AgreementWorkflowMap::nextStatus($from);
        if ($nextStatus !== $validNext) {
            abort(403, "Invalid forward target '{$nextStatus}' for stage '{$from}'. Must be '{$validNext}'.");
        }

        $handler = AgreementWorkflowMap::handlerForStatus($nextStatus);

        if ($nextStatus === 'active_agreement') {
            $agreement->signed_at = $agreement->signed_at ?: now();
            $agreement->status = 'active';
        }

        $agreement->workflow_status = $nextStatus;
        if ($targetUserId) {
            $targetUser = User::find($targetUserId);
            $agreement->current_handler = $targetUser?->name ?? $handler;
        } else {
            $agreement->current_handler = $handler;
        }
        $agreement->save();

        $forwardRemarks = $remarks;
        if ($versionId) {
            $forwardRemarks = ($forwardRemarks ? $forwardRemarks.' ' : '')."(version_id: {$versionId})";
        }

        WorkflowHistory::create([
            'agreement_id' => $agreement->id,
            'action' => 'Forwarded',
            'performed_by' => $actor->name,
            'from_status' => $from,
            'to_status' => $nextStatus,
            'remarks' => $forwardRemarks,
        ]);

        $targetUsers = $this->getTargetUsers($nextStatus, $targetUserId);

        $humanFrom = AgreementWorkflowMap::humanizeStatus($from) ?? $from;
        $humanNext = AgreementWorkflowMap::humanizeStatus($nextStatus) ?? $nextStatus;
        $agreementType = $agreement->type ?? 'Agreement';

        if ($nextStatus === 'administrative_aid') {
            $title = 'Log Agreement';
            $message = "{$agreement->title} ({$agreementType}) was moved from {$humanFrom} to {$humanNext} by {$actor->name}. Please record administrative details and timestamps.";
        } elseif ($nextStatus === 'active_agreement') {
            $title = "{$agreementType} is now active";
            $message = "{$agreement->title} ({$agreementType}) was forwarded from {$humanFrom} and is now active.";
        } else {
            $title = "{$agreementType} moved to {$humanNext}";
            $message = "{$agreement->title} ({$agreementType}) was moved from {$humanFrom} to {$humanNext} by {$actor->name}";
        }

        $this->notifyUsers($targetUsers, $title, $message);

        return [
            'from' => $from,
            'to' => $nextStatus,
            'agreement_type' => $agreementType,
            'version_id' => $versionId,
        ];
    }

    public function returnWorkflow(Agreement $agreement, User $actor, ?string $remarks = null, ?string $returnToStatus = null): array
    {
        $from = $agreement->workflow_status;

        if ($from === 'administrative_aid' && $returnToStatus === 'attorney_initials') {
            $to = 'attorney_initials';
        } else {
            $to = AgreementWorkflowMap::previousStatusForReturn($from);
        }
        $humanFrom = AgreementWorkflowMap::humanizeStatus($from) ?? $from;
        $agreementType = $agreement->type ?? 'Agreement';

        if ($from === 'legal_assistant_ii') {
            $sender = User::find($agreement->submitted_by);
            $agreement->workflow_status = 'draft';
            $agreement->current_handler = $sender?->name ?? 'Sender';
            $agreement->save();

            WorkflowHistory::create([
                'agreement_id' => $agreement->id,
                'action' => 'Returned',
                'performed_by' => $actor->name,
                'from_status' => $from,
                'to_status' => 'draft',
                'remarks' => $remarks ?? "Returned to sender ({$sender?->name})",
            ]);

            $title = "{$agreementType} returned for revision";
            $message = "{$agreement->title} ({$agreementType}) was returned by {$actor->name} for revision. Please review and resubmit.";

            if ($sender) {
                $this->notifyUsers(collect([$sender]), $title, $message);
            }

            return [
                'from' => $from,
                'to' => 'draft',
                'agreement_type' => $agreementType,
            ];
        }

        $agreement->workflow_status = $to;
        $toHandler = AgreementWorkflowMap::handlerForStatus($to);
        $agreement->current_handler = $toHandler;
        $agreement->status = $to === 'active_agreement' ? 'active' : 'for_review';
        $agreement->save();

        WorkflowHistory::create([
            'agreement_id' => $agreement->id,
            'action' => 'Returned',
            'performed_by' => $actor->name,
            'from_status' => $from,
            'to_status' => $to,
            'remarks' => $remarks,
        ]);

        $targetUsers = $this->getTargetUsers($to);

        $humanTo = AgreementWorkflowMap::humanizeStatus($to) ?? $to;

        if ($to === 'administrative_aid') {
            $title = 'Log Agreement';
            $message = "{$agreement->title} ({$agreementType}) was returned from {$humanFrom} to {$humanTo} by {$actor->name}. Please record administrative details and timestamps.";
        } else {
            $title = "{$agreementType} returned to {$humanTo}";
            $message = "{$agreement->title} ({$agreementType}) was returned from {$humanFrom} to {$humanTo} by {$actor->name}";
        }

        $this->notifyUsers($targetUsers, $title, $message);

        return [
            'from' => $from,
            'to' => $to,
            'agreement_type' => $agreementType,
        ];
    }

    public function disable(Agreement $agreement): void
    {
        $agreement->status = 'terminated';
        $agreement->workflow_status = 'terminated';
        $agreement->current_handler = null;
        $agreement->save();
    }

    private function getTargetUsers(string $status, ?int $targetUserId = null): Collection
    {
        if ($targetUserId) {
            $targetUser = User::find($targetUserId);

            return $targetUser ? collect([$targetUser]) : collect();
        }

        if ($status === 'active_agreement') {
            return collect();
        }

        if (AgreementWorkflowMap::isAttorneyStage($status)) {
            return User::where('role', 'coordinator')
                ->where('coordinator_stage', 'attorney')
                ->get();
        }

        return User::where('role', 'coordinator')
            ->where('coordinator_stage', $status)
            ->get();
    }

    private function notifyUsers(iterable $users, string $title, string $message): void
    {
        foreach ($users as $user) {
            Notification::create([
                'title' => $title,
                'message' => $message,
                'is_read' => false,
                'user_id' => $user->id,
            ]);
        }
    }
}
