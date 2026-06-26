<?php

namespace Tests\Feature;

use App\Mail\AgreementExpiring;
use App\Models\Agreement;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendExpirationRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_notifications_for_submitter_and_roles()
    {
        Mail::fake();

        $submitter = User::factory()->create(['role' => 'authorized_personnel']);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_ii']);
        $president = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'president_approval']);

        $agreement = Agreement::create([
            'title' => 'Soon Expiring',
            'type' => 'MOA',
            'partner_organization' => 'Partner X',
            'status' => 'active',
            'workflow_status' => 'active_agreement',
            'current_handler' => 'Authorized Personnel',
            'submitted_by' => $submitter->id,
            'expires_at' => now()->addDays(10),
        ]);

        $this->artisan('agreements:send-expiration-reminders', ['days' => 30])->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'title' => 'Agreement expiring soon: Soon Expiring',
            'user_id' => $submitter->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'title' => 'Agreement expiring soon: Soon Expiring',
            'user_id' => $coordinator->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'title' => 'Agreement expiring soon: Soon Expiring',
            'user_id' => $president->id,
        ]);

        Mail::assertQueued(AgreementExpiring::class, 3);
    }

    public function test_command_includes_coordinators_in_reminder_role_aliases()
    {
        Mail::fake();

        $submitter = User::factory()->create(['role' => 'authorized_personnel']);
        $coordinatorLA2 = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_ii']);
        $coordinatorLA3 = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_iii']);

        Agreement::create([
            'title' => 'Coordinator Reminder',
            'type' => 'MOU',
            'partner_organization' => 'Partner Y',
            'status' => 'active',
            'workflow_status' => 'active_agreement',
            'current_handler' => 'Coordinator',
            'submitted_by' => $submitter->id,
            'expires_at' => now()->addDays(5),
        ]);

        $this->artisan('agreements:send-expiration-reminders', ['days' => 30])->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'title' => 'Agreement expiring soon: Coordinator Reminder',
            'user_id' => $coordinatorLA2->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'title' => 'Agreement expiring soon: Coordinator Reminder',
            'user_id' => $coordinatorLA3->id,
        ]);

        Mail::assertQueued(AgreementExpiring::class, 3);
    }

    public function test_command_does_not_create_duplicate_notifications_for_same_day_and_window()
    {
        Mail::fake();

        $submitter = User::factory()->create(['role' => 'authorized_personnel']);
        User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_ii']);
        User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'president_approval']);

        Agreement::create([
            'title' => 'Idempotent Reminder Agreement',
            'type' => 'MOA',
            'partner_organization' => 'Partner I',
            'status' => 'active',
            'workflow_status' => 'active_agreement',
            'current_handler' => 'Authorized Personnel',
            'submitted_by' => $submitter->id,
            'expires_at' => now()->addDays(10),
        ]);

        $this->artisan('agreements:send-expiration-reminders', ['days' => 30])->assertExitCode(0);
        $this->artisan('agreements:send-expiration-reminders', ['days' => 30])->assertExitCode(0);

        $this->assertSame(
            3,
            Notification::where('title', 'Agreement expiring soon: Idempotent Reminder Agreement')->count()
        );

        Mail::assertQueued(AgreementExpiring::class, 3);
    }
}
