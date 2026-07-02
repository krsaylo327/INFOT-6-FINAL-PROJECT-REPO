<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgreementNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_forwarding_creates_notification()
    {
        $user = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_ii']);
        $target = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_iii']);

        $agreement = Agreement::create([
            'title' => 'Notify Agreement',
            'type' => 'MOA',
            'partner_organization' => 'Partner Z',
            'status' => 'for_review',
            'workflow_status' => 'legal_assistant_ii',
            'current_handler' => 'Legal Assistant II',
            'submitted_by' => 1,
        ]);

        $this->actingAs($user)->post("/agreements/{$agreement->id}/forward", [
            'next_status' => 'legal_assistant_iii',
        ])->assertStatus(302);

        $this->assertDatabaseHas('notifications', [
            'title' => 'MOA moved to Legal Assistant Iii',
            'user_id' => $target->id,
        ]);
    }

    public function test_return_creates_notification()
    {
        $user = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'attorney']);
        $target = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_iii']);

        $agreement = Agreement::create([
            'title' => 'Notify Return',
            'type' => 'MOU',
            'partner_organization' => 'Partner R',
            'status' => 'for_review',
            'workflow_status' => 'attorney_review',
            'current_handler' => 'Attorney',
            'submitted_by' => 1,
        ]);

        $this->actingAs($user)->post("/agreements/{$agreement->id}/return", [
            'remarks' => 'Please revise',
        ])->assertStatus(302);

        $this->assertDatabaseHas('notifications', [
            'title' => 'MOU returned to Legal Assistant Iii',
            'user_id' => $target->id,
        ]);
    }

    public function test_president_approval_creates_active_agreement()
    {
        $sender = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => null]);
        $president = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'president_approval']);

        $agreement = Agreement::create([
            'title' => 'Approved Reception',
            'type' => 'MOA',
            'partner_organization' => 'Partner A',
            'status' => 'for_review',
            'workflow_status' => 'president_approval',
            'current_handler' => 'President',
            'submitted_by' => $sender->id,
        ]);

        $this->actingAs($president)->post("/agreements/{$agreement->id}/forward", [
            'next_status' => 'active_agreement',
            'remarks' => 'President approved agreement',
        ])->assertStatus(302);

        $this->assertDatabaseHas('agreements', [
            'id' => $agreement->id,
            'workflow_status' => 'active_agreement',
            'status' => 'active',
        ]);
    }
}
