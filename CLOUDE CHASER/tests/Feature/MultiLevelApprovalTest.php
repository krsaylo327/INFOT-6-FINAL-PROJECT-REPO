<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\TravelRequest;
use App\Models\User;
use App\Services\ApprovalChainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiLevelApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Department $dept;
    private User $traveler;
    private User $approverL1;
    private User $approverL2;
    private User $approverL3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept = Department::create(['name' => 'College of Computer and Information Studies', 'abbreviation' => 'CCIS']);

        $this->traveler = User::create([
            'name'          => 'Juan Traveler',
            'email'         => 'juan@antiquespride.edu.ph',
            'password'      => Hash::make('password'),
            'role'          => 'traveler',
            'status'        => 'active',
            'department_id' => $this->dept->id,
        ]);

        $this->approverL1 = User::create([
            'name'           => 'L1 Dean',
            'email'          => 'l1@antiquespride.edu.ph',
            'password'       => Hash::make('password'),
            'role'           => 'approver',
            'status'         => 'active',
            'approver_level' => 1,
            'department_id'  => $this->dept->id,
        ]);

        $this->approverL2 = User::create([
            'name'           => 'L2 Finance',
            'email'          => 'l2@antiquespride.edu.ph',
            'password'       => Hash::make('password'),
            'role'           => 'approver',
            'status'         => 'active',
            'approver_level' => 2,
            'department_id'  => $this->dept->id,
        ]);

        $this->approverL3 = User::create([
            'name'           => 'L3 VP',
            'email'          => 'l3@antiquespride.edu.ph',
            'password'       => Hash::make('password'),
            'role'           => 'approver',
            'status'         => 'active',
            'approver_level' => 3,
            'department_id'  => $this->dept->id,
        ]);
    }

    public function test_low_cost_uses_single_level_policy(): void
    {
        $tr = $this->makeTravelRequest(estimated_cost: 2500);
        $service = app(ApprovalChainService::class);

        $this->assertSame(1, $service->policyLevelsFor($tr));
    }

    public function test_medium_cost_uses_two_levels(): void
    {
        $tr = $this->makeTravelRequest(estimated_cost: 15000);
        $this->assertSame(2, app(ApprovalChainService::class)->policyLevelsFor($tr));
    }

    public function test_high_cost_uses_three_levels(): void
    {
        $tr = $this->makeTravelRequest(estimated_cost: 50000);
        $this->assertSame(3, app(ApprovalChainService::class)->policyLevelsFor($tr));
    }

    public function test_self_request_store_creates_level_1_only_for_low_cost(): void
    {
        $resp = $this->actingAs($this->traveler)->post('/travel-requests', [
            'department_id'  => $this->dept->id,
            'destination'    => 'Manila',
            'purpose'        => 'Conference attendance trip',
            'date_from'      => now()->addDays(5)->toDateString(),
            'date_to'        => now()->addDays(7)->toDateString(),
            'estimated_cost' => 3000,
        ]);

        $resp->assertRedirect();

        $tr = TravelRequest::first();
        $this->assertNotNull($tr);
        $this->assertSame(1, $tr->approvals()->count());
        $this->assertSame($this->approverL1->id, $tr->approvals()->first()->approver_id);
        $this->assertSame('pending', $tr->approvals()->first()->action);

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'request.submitted',
            'auditable_type' => TravelRequest::class,
            'auditable_id'   => $tr->id,
        ]);
    }

    public function test_approving_level_1_advances_to_level_2_for_medium_cost(): void
    {
        $tr = $this->makeTravelRequest(estimated_cost: 15000);
        app(ApprovalChainService::class)->initialize($tr);

        $l1 = $tr->approvals()->first();

        $this->actingAs($this->approverL1)
            ->patch("/approvals/{$l1->id}", [
                'action'       => 'approved',
                'remarks'      => 'OK from Dean',
                'security_key' => 'password',
            ])
            ->assertRedirect();

        $tr->refresh();

        $this->assertSame(2, $tr->approvals()->count());
        $this->assertSame('pending', $tr->status);

        $l2 = $tr->approvals()->where('level', 2)->first();
        $this->assertNotNull($l2);
        $this->assertSame($this->approverL2->id, $l2->approver_id);
        $this->assertSame('pending', $l2->action);
    }

    public function test_approving_final_level_marks_request_approved(): void
    {
        $tr = $this->makeTravelRequest(estimated_cost: 50000);
        $service = app(ApprovalChainService::class);
        $service->initialize($tr);

        // Approve L1
        $l1 = $tr->approvals()->where('level', 1)->first();
        $this->actingAs($this->approverL1)->patch("/approvals/{$l1->id}", [
            'action'       => 'approved',
            'security_key' => 'password',
        ])->assertRedirect();

        // Approve L2
        $l2 = $tr->fresh()->approvals()->where('level', 2)->first();
        $this->actingAs($this->approverL2)->patch("/approvals/{$l2->id}", [
            'action'       => 'approved',
            'security_key' => 'password',
        ])->assertRedirect();

        // Approve L3
        $l3 = $tr->fresh()->approvals()->where('level', 3)->first();
        $this->actingAs($this->approverL3)->patch("/approvals/{$l3->id}", [
            'action'       => 'approved',
            'security_key' => 'password',
        ])->assertRedirect();

        $tr->refresh();

        $this->assertSame('approved', $tr->status);
        $this->assertSame(3, $tr->approvals()->count());
        $this->assertSame(3, $tr->approvals()->where('action', 'approved')->count());
    }

    public function test_rejection_short_circuits_chain(): void
    {
        $tr = $this->makeTravelRequest(estimated_cost: 50000);
        app(ApprovalChainService::class)->initialize($tr);

        $l1 = $tr->approvals()->first();

        $this->actingAs($this->approverL1)
            ->patch("/approvals/{$l1->id}", [
                'action'       => 'rejected',
                'remarks'      => 'Budget exceeded',
                'security_key' => 'password',
            ])
            ->assertRedirect();

        $tr->refresh();

        $this->assertSame('rejected', $tr->status);
        $this->assertSame(1, $tr->approvals()->count()); // no L2/L3 created

        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'approval.rejected',
            'auditable_id' => $tr->id,
        ]);
    }

    public function test_level_2_approver_cannot_act_before_level_1(): void
    {
        $tr = $this->makeTravelRequest(estimated_cost: 15000);
        app(ApprovalChainService::class)->initialize($tr);

        // Manually create a pending L2 approval to simulate race condition
        $tr->approvals()->create([
            'approver_id' => $this->approverL2->id,
            'level'       => 2,
            'action'      => 'pending',
        ]);

        $l2 = $tr->approvals()->where('level', 2)->first();

        $this->actingAs($this->approverL2)
            ->patch("/approvals/{$l2->id}", [
                'action'       => 'approved',
                'security_key' => 'password',
            ])
            ->assertRedirect();

        // L2 should still be pending because L1 hasn't acted
        $this->assertSame('pending', $l2->fresh()->action);
    }

    public function test_approver_index_hides_later_level_approvals(): void
    {
        $tr = $this->makeTravelRequest(estimated_cost: 15000);
        app(ApprovalChainService::class)->initialize($tr);

        // Pre-create an L2 row (which normally wouldn't exist until L1 acts — simulates a bug-case)
        $tr->approvals()->create([
            'approver_id' => $this->approverL2->id,
            'level'       => 2,
            'action'      => 'pending',
        ]);

        // L1 should see the L1 item
        $this->actingAs($this->approverL1)
            ->get('/approvals')
            ->assertStatus(200);

        // L2 should see NOTHING in their list because L1 is still pending
        $resp = $this->actingAs($this->approverL2)->get('/approvals');
        $resp->assertStatus(200);
        $this->assertCount(0, $resp->viewData('approvals'));
    }

    public function test_audit_log_is_written_on_each_approval_action(): void
    {
        $tr = $this->makeTravelRequest(estimated_cost: 3000);
        app(ApprovalChainService::class)->initialize($tr);

        $l1 = $tr->approvals()->first();

        $this->actingAs($this->approverL1)->patch("/approvals/{$l1->id}", [
            'action'       => 'approved',
            'remarks'      => 'Looks good',
            'security_key' => 'password',
        ])->assertRedirect();

        $this->assertSame(1, AuditLog::where('action', 'approval.approved')->count());

        $entry = AuditLog::where('action', 'approval.approved')->first();
        $this->assertSame($tr->id, (int) $entry->auditable_id);
        $this->assertSame(TravelRequest::class, $entry->auditable_type);
        $this->assertSame(1, $entry->metadata['level']);
        $this->assertTrue($entry->metadata['fully_approved']);
    }

    // ----------------- helpers -----------------

    private function makeTravelRequest(float $estimated_cost): TravelRequest
    {
        return TravelRequest::create([
            'request_no'     => 'TR-TEST-' . uniqid(),
            'user_id'        => $this->traveler->id,
            'department_id'  => $this->dept->id,
            'destination'    => 'Manila',
            'purpose'        => 'Conference',
            'date_from'      => now()->addDays(5),
            'date_to'        => now()->addDays(7),
            'estimated_cost' => $estimated_cost,
            'status'         => 'pending',
            'type'           => 'self',
            'submitted_at'   => now(),
        ]);
    }
}
