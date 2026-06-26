<?php

namespace App\Console\Commands;

use App\Models\Agreement;
use Illuminate\Console\Command;

class UpdateAgreementStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agreements:update-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recompute and persist agreement statuses (draft, for_review, active, expired, renewed, terminated)';

    public function handle(): int
    {
        // Auto-expire agreements
        Agreement::whereDate('expires_at', '<', now())
            ->whereNotIn('status', ['terminated', 'disabled'])
            ->update(['status' => 'expired']);

        $count = 0;

        Agreement::query()->chunkById(200, function ($agreements) use (&$count): void {
            foreach ($agreements as $agreement) {
                $originalStatus = $agreement->status;
                $agreement->syncStatus();

                if ($agreement->status !== $originalStatus) {
                    $agreement->save();
                    $count++;
                }
            }
        });

        $this->info("Updated statuses for {$count} agreements.");

        return self::SUCCESS;
    }
}
