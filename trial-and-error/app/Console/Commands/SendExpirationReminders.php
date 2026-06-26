<?php

namespace App\Console\Commands;

use App\Mail\AgreementExpiring;
use App\Models\Agreement;
use App\Models\Notification;
use App\Models\User;
use App\Support\AgreementWorkflowMap;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendExpirationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agreements:send-expiration-reminders {days? : Optional number of days ahead to process reminders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for agreements expiring within the given number of days';

    private const DEFAULT_REMINDER_DAYS = [30, 14, 7, 1];

    private const DEFAULT_NOTIFY_ROLES = [
        'authorized_personnel',
        'coordinator',
        'administrative_aid',
        'president',
    ];

    public function handle(): int
    {
        $now = now();
        $reminderDays = $this->resolveReminderDays($this->argument('days'));
        $notifyRoles = $this->resolveNotifyRoles();
        $roleRecipientIds = User::whereIn('role', $notifyRoles)->pluck('id')->all();

        $totalCreated = 0;

        foreach ($reminderDays as $daysAhead) {
            $threshold = $now->copy()->addDays($daysAhead);

            $agreements = Agreement::whereNotNull('expires_at')
                ->whereBetween('expires_at', [$now, $threshold])
                ->whereIn('status', ['active', 'for_review'])
                ->get();

            foreach ($agreements as $agreement) {
                $recipientIds = $this->recipientIdsForAgreement($agreement, $roleRecipientIds);
                $totalCreated += $this->createReminderNotifications($agreement, $recipientIds, $daysAhead, $now);
            }
        }

        $this->info("Created {$totalCreated} expiration reminder notifications.");

        return self::SUCCESS;
    }

    private function resolveReminderDays(?string $daysArg): array
    {
        if ($daysArg === null) {
            return config('agreements.reminder_days', self::DEFAULT_REMINDER_DAYS);
        }

        return [max(1, (int) $daysArg)];
    }

    private function resolveNotifyRoles(): array
    {
        $configuredRoles = config('agreements.notify_roles', self::DEFAULT_NOTIFY_ROLES);
        $aliases = array_merge(...array_map(fn ($role) => AgreementWorkflowMap::aliasesForRole($role), $configuredRoles));

        return array_values(array_unique($aliases));
    }

    private function recipientIdsForAgreement(Agreement $agreement, array $roleRecipientIds): array
    {
        $subscribedUserIds = $agreement->subscriptions()
            ->where('notify_on_expiration', true)
            ->pluck('user_id')
            ->all();

        if (! empty($subscribedUserIds)) {
            return $subscribedUserIds;
        }

        $recipientIds = [];

        if ($agreement->submitted_by) {
            $recipientIds[] = $agreement->submitted_by;
        }

        return array_values(array_unique(array_merge($recipientIds, $roleRecipientIds)));
    }

    private function createReminderNotifications(Agreement $agreement, array $recipientIds, int $daysAhead, Carbon $now): int
    {
        $expiresAtStr = $agreement->expires_at
            ? Carbon::parse($agreement->expires_at)->toDateString()
            : 'N/A';

        $title = "Agreement expiring soon: {$agreement->title}";
        $message = "Agreement '{$agreement->title}' expires on {$expiresAtStr}";
        $reminderDate = $now->toDateString();
        $created = 0;

        foreach ($recipientIds as $uid) {
            $dedupeKey = implode(':', [
                'expiration-reminder',
                $agreement->id,
                $uid,
                $daysAhead,
                $reminderDate,
            ]);

            $notification = Notification::firstOrCreate(
                ['dedupe_key' => $dedupeKey],
                [
                    'title' => $title,
                    'message' => $message,
                    'is_read' => false,
                    'user_id' => $uid,
                ]
            );

            if (! $notification->wasRecentlyCreated) {
                continue;
            }

            $this->queueReminderEmail($uid, $agreement, $daysAhead);
            $created++;
        }

        return $created;
    }

    private function queueReminderEmail(int $userId, Agreement $agreement, int $daysAhead): void
    {
        try {
            $user = User::find($userId);

            if ($user && $user->email) {
                Mail::to($user)->queue(new AgreementExpiring($agreement, $daysAhead));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
