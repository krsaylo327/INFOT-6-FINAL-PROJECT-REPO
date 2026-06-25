<?php

namespace App\Console\Commands;

use App\Models\Approval;
use App\Models\User;
use App\Notifications\ApprovalEscalated;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

class EscalateApprovals extends Command
{
    protected $signature = 'approvals:escalate
        {--days=3 : Number of days a pending approval can sit before being escalated}
        {--dry-run : Show which approvals would be escalated without changing anything}';

    protected $description = 'Flag approvals that have been pending longer than N days (writes an audit log entry).';

    public function handle(): int
    {
        $days   = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = now()->subDays($days);

        $stale = Approval::with(['travelRequest', 'approver'])
            ->where('action', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->get();

        if ($stale->isEmpty()) {
            $this->info("No approvals older than {$days} day(s) are pending.");
            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d stale approval%s older than %d day(s).',
            $dryRun ? 'Would escalate' : 'Escalating',
            $stale->count(),
            $stale->count() === 1 ? '' : 's',
            $days,
        ));

        foreach ($stale as $approval) {
            $tr = $approval->travelRequest;

            $this->line(sprintf(
                '  #%s  Level %d  approver=%s  pending since %s',
                $tr->request_no ?? "TR-{$approval->travel_request_id}",
                $approval->level,
                $approval->approver->name ?? "user-{$approval->approver_id}",
                $approval->created_at->diffForHumans(),
            ));

            if (!$dryRun && $tr) {
                AuditLogger::log('approval.escalated', $tr, [
                    'approval_id'  => $approval->id,
                    'level'        => $approval->level,
                    'approver_id'  => $approval->approver_id,
                    'pending_days' => (int) $approval->created_at->diffInDays(now()),
                    'threshold_days' => $days,
                ], userId: null);

                $approval->approver?->notify(new ApprovalEscalated($approval));
                User::where('role', 'admin')->each(fn($admin) => $admin->notify(new ApprovalEscalated($approval)));
            }
        }

        return self::SUCCESS;
    }
}
