<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * End-to-end integration tests covering the full UA-TMIP lifecycle:
 *
 *     assigner  ─►  traveler  ─►  L1 approver  ─►  L2 approver  ─► approved
 *     (approver)    (ack)          (approve)       (approve)
 *
 * Verifies the entire orchestration of:
 *   • AssignmentController::store       (approver creates the trip)
 *   • AssignmentController::acknowledge (traveler accepts → kicks off approval chain)
 *   • ApprovalController::update        (each approver acts)
 *   • ApprovalChainService::advance     (auto-creates next level)
 *   • AuditLogger                       (every milestone is logged)
 */
class AssignmentToApprovalEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private Department $dept;
    private User $traveler;
    private User $approverL1; // Also acts as the assigner (realistic scenario:
                              // a Dean both assigns travel AND is the L1 reviewer)
    private User $approverL2; // Finance Officer

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept = Department::create(['name' => 'College of Computer and Information Studies', 'abbreviation' => 'CCIS']);

        // Single L1 approver — keeps buildChain()'s "->first()" deterministic
        // and mirrors the real seeder layout (one approver per level).
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

        $this->traveler = User::create([
            'name'          => 'Juan Traveler',
            'email'         => 'juan@antiquespride.edu.ph',
            'password'      => Hash::make('password'),
            'role'          => 'traveler',
            'status'        => 'active',
            'department_id' => $this->dept->id,
        ]);
    }

    public function test_full_happy_path_assignment_to_final_approval_for_medium_cost_trip(): void
    {
        // ─── 1. Approver ASSIGNS a trip (cost forces a 2-level chain) ─────
        $this->actingAs($this->approverL1)->post('/assignments', [
            'user_id'        => $this->traveler->id,
            'destination'    => 'Cebu',
            'purpose'        => 'Regional ICT Summit 2025',
            'date_from'      => now()->addDays(10)->toDateString(),
            'date_to'        => now()->addDays(12)->toDateString(),
            'estimated_cost' => 15000, // 5k < x ≤ 20k → 2 levels
        ])->assertRedirect(route('assignments.index'));

        /** @var TravelRequest $tr */
        $tr = TravelRequest::firstOrFail();

        $this->assertSame('assigned', $tr->status);
        $this->assertSame('assigned', $tr->type);
        $this->assertSame($this->approverL1->id, $tr->assigned_by);
        $this->assertSame($this->traveler->id, $tr->user_id);
        $this->assertNull($tr->acknowledged_at);
        $this->assertNull($tr->submitted_at);
        $this->assertCount(0, $tr->approvals); // no chain yet
        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'assignment.created',
            'auditable_id' => $tr->id,
            'user_id'      => $this->approverL1->id,
        ]);

        // ─── 2. Traveler ACKNOWLEDGES → chain initializes at L1 ────────────
        $this->actingAs($this->traveler)
            ->post(route('assignments.acknowledge', $tr))
            ->assertRedirect(route('travel-requests.show', $tr));

        $tr->refresh();
        $this->assertSame('pending', $tr->status);
        $this->assertNotNull($tr->acknowledged_at);
        $this->assertNotNull($tr->submitted_at);

        $this->assertCount(1, $tr->approvals);
        $l1 = $tr->approvals->firstWhere('level', 1);
        $this->assertNotNull($l1);
        $this->assertSame($this->approverL1->id, $l1->approver_id);
        $this->assertSame('pending', $l1->action);

        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'assignment.acknowledged',
            'auditable_id' => $tr->id,
            'user_id'      => $this->traveler->id,
        ]);

        // ─── 3. L1 approver APPROVES → chain advances to L2 ───────────────
        $this->actingAs($this->approverL1)
            ->patch("/approvals/{$l1->id}", [
                'action'       => 'approved',
                'remarks'      => 'Valid trip; budget reasonable.',
                'security_key' => 'password',
            ])
            ->assertRedirect();

        $tr->refresh()->load('approvals');

        $this->assertSame('pending', $tr->status, 'Still pending because L2 must also act');
        $this->assertCount(2, $tr->approvals, 'L2 approval row should now exist');

        $l1Fresh = $tr->approvals->firstWhere('level', 1);
        $this->assertSame('approved', $l1Fresh->action);
        $this->assertNotNull($l1Fresh->acted_at);
        $this->assertSame('Valid trip; budget reasonable.', $l1Fresh->remarks);

        $l2 = $tr->approvals->firstWhere('level', 2);
        $this->assertNotNull($l2);
        $this->assertSame($this->approverL2->id, $l2->approver_id);
        $this->assertSame('pending', $l2->action);

        // ─── 4. L2 approver APPROVES → request fully approved ─────────────
        $this->actingAs($this->approverL2)
            ->patch("/approvals/{$l2->id}", [
                'action'       => 'approved',
                'remarks'      => 'Funds available.',
                'security_key' => 'password',
            ])
            ->assertRedirect();

        $tr->refresh()->load('approvals');

        $this->assertSame('approved', $tr->status, 'Final level approval finalizes the request');
        $this->assertCount(2, $tr->approvals, 'No L3 because medium-cost policy caps at 2 levels');
        $this->assertSame(
            2,
            $tr->approvals->where('action', 'approved')->count(),
            'Both levels must show approved'
        );

        // ─── 5. Audit log trail tells the whole story ─────────────────────
        $actions = AuditLog::where('auditable_type', TravelRequest::class)
            ->where('auditable_id', $tr->id)
            ->orderBy('created_at')
            ->pluck('action')
            ->all();

        $this->assertSame(
            [
                'assignment.created',
                'assignment.acknowledged',
                'approval.approved', // L1
                'approval.approved', // L2 (final)
            ],
            $actions,
            'Audit trail should capture every lifecycle milestone in order'
        );

        // The final audit entry should flag fully_approved = true
        $finalEntry = AuditLog::where('auditable_id', $tr->id)
            ->where('action', 'approval.approved')
            ->latest('id')
            ->first();

        $this->assertTrue($finalEntry->metadata['fully_approved'] ?? false);
        $this->assertSame(2, $finalEntry->metadata['level'] ?? null);
    }

    public function test_traveler_declining_an_assignment_never_creates_approval_chain(): void
    {
        $this->actingAs($this->approverL1)->post('/assignments', [
            'user_id'        => $this->traveler->id,
            'destination'    => 'Davao',
            'purpose'        => 'Accreditation site visit',
            'date_from'      => now()->addDays(5)->toDateString(),
            'date_to'        => now()->addDays(7)->toDateString(),
            'estimated_cost' => 25000, // would be 3-level if accepted
        ])->assertRedirect();

        $tr = TravelRequest::firstOrFail();

        $this->actingAs($this->traveler)
            ->post(route('assignments.decline', $tr), [
                'reason' => 'Schedule conflict.',
            ])
            ->assertRedirect(route('dashboard'));

        $tr->refresh();

        $this->assertSame('declined', $tr->status);
        $this->assertCount(0, $tr->approvals, 'Declined assignments must not spawn approvals');

        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'assignment.declined',
            'auditable_id' => $tr->id,
            'user_id'      => $this->traveler->id,
        ]);

        // The audit trail should NOT contain any approval.* entries
        $this->assertSame(
            0,
            AuditLog::where('auditable_id', $tr->id)
                ->where('action', 'like', 'approval.%')
                ->count()
        );
    }
}
