<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SendExpirationReminders::class,
        Commands\UpdateAgreementStatuses::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // run daily to create reminders for agreements expiring soon
        // update agreement statuses before sending reminders
        $schedule->command('agreements:update-statuses')
            ->dailyAt('00:05')
            ->withoutOverlapping(30)
            ->onOneServer();

        $schedule->command('agreements:send-expiration-reminders')
            ->dailyAt('00:15')
            ->withoutOverlapping(30)
            ->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        require base_path('routes/console.php');
    }
}
