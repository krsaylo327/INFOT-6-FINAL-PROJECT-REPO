<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Department;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AssignmentFlowTest extends TestCase
{
    use RefreshDatabase;

    private Department $ccis;
    private Department $finance;
    private User $admin;
    private User $approver;
    private User $traveler;
    private User $otherTraveler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ccis    = Department::create(['name' => 'College of Computer and Information Studies', 'abbreviation' => 'CCIS']);
        $this->finance = Department::create(['name' => 'Finance']);

        $this->admin = User::create([
            'name'          => 'Admin',
            'email'         => 'admin@antiquespride.edu.ph',
            'password'      => Hash::make('password'),
            'role'          => 'admin',
            'status'        => 'active',
            'department_id' => $this->ccis->id,
        ]);

        $this->approver = User::create([
            'name'           => 'Approver',
            'email'          => 'approver@antiquespride.edu.ph',
            'password'       => Hash::make('password'),
            'role'           => 'approver',
            'status'         => 'active',
            'approver_level' => 1,
            'department_id'  => $this->finance->id,
        ]);

        $this->traveler = User::create([
            'name'          => 'Traveler',
            'email'         => 'traveler@antiquespride.edu.ph',
            'password'      => Hash::make('password'),
            'role'          => 'traveler',
            'status'        => 'active',
            'department_id' => $this->ccis->id,
        ]);

        $this->otherTraveler = User::create([
            'name'          => 'Other Traveler',
            'email'         => 'other@antiquespride.edu.ph',
            'password'      => Hash::make('password'),
            'role'          => 'traveler',
            'status'        => 'active',
            'department_id' => $this->ccis->id,
        ]);
    }

    /* -----------------------------------------------------------------
     | 1. Role gates: who may reach the assignment UI
     | ---------------------------------------------------------------*/

    public function test_guest_cannot_access_assignment_routes(): void
    {
        $this->get(route('assignments.index'))->assertRedirect(route('login'));
        $this->get(route('assignments.create'))->assertRedirect(route('login'));
    }

    public function test_traveler_cannot_view_assignment_index(): void
    {
        $this->actingAs($this->traveler)
            ->get(route('assignments.index'))
            ->assertForbidden();
    }

    public function test_traveler_cannot_view_assignment_create(): void
    {
        $this->actingAs($this->traveler)
            ->get(route('assignments.create'))
            ->assertForbidden();
    }

    public function test_approver_can_view_assignment_create_form(): void
    {
        $this->actingAs($this->approver)
            ->get(route('assignments.create'))
            ->assertOk()
            ->assertSee($this->traveler->name);
    }

    public function test_admin_can_view_assignment_create_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('assignments.create'))
            ->assertOk();
    }

    /* -----------------------------------------------------------------
     | 2. Creating an assignment
     | ---------------------------------------------------------------*/

    public function test_approver_can_create_an_assignment(): void
    {
        $payload = [
            'user_id'        => $this->traveler->id,
            'destination'    => 'Manila',
            'purpose'        => 'Conference attendance trip',
            'date_from'      => now()->addWeek()->toDateString(),
            'date_to'        => now()->addWeek()->addDays(2)->toDateString(),
            'estimated_cost' => 5000,
        ];

        $this->actingAs($this->approver)
            ->post(route('assignments.store'), $payload)
            ->assertRedirect(route('assignments.index'));

        $this->assertDatabaseHas('travel_requests', [
            'user_id'         => $this->traveler->id,
            'department_id'   => $this->ccis->id,
            'destination'     => 'Manila',
            'status'          => 'assigned',
            'type'            => 'assigned',
            'assigned_by'     => $this->approver->id,
            'acknowledged_at' => null,
            'submitted_at'    => null,
        ]);
    }

    public function test_cannot_assign_travel_to_a_non_traveler(): void
    {
        $this->actingAs($this->approver)
            ->post(route('assignments.store'), [
                'user_id'        => $this->admin->id, // not a traveler
                'destination'    => 'Manila',
                'purpose'        => 'Conference attendance trip',
                'date_from'      => now()->addWeek()->toDateString(),
                'date_to'        => now()->addWeek()->addDay()->toDateString(),
                'estimated_cost' => 1000,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('travel_requests', [
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_date_to_must_be_after_or_equal_to_date_from(): void
    {
        $this->actingAs($this->approver)
            ->post(route('assignments.store'), [
                'user_id'        => $this->traveler->id,
                'destination'    => 'Manila',
                'purpose'        => 'Conference attendance trip',
                'date_from'      => now()->addWeek()->toDateString(),
                'date_to'        => now()->addDays(1)->toDateString(), // before
                'estimated_cost' => 1000,
            ])
            ->assertSessionHasErrors('date_to');
    }

    public function test_traveler_cannot_create_an_assignment(): void
    {
        $this->actingAs($this->traveler)
            ->post(route('assignments.store'), [
                'user_id'        => $this->otherTraveler->id,
                'destination'    => 'Manila',
                'purpose'        => 'Conference attendance trip',
                'date_from'      => now()->addWeek()->toDateString(),
                'date_to'        => now()->addWeek()->addDay()->toDateString(),
                'estimated_cost' => 1000,
            ])
            ->assertForbidden();
    }

    /* -----------------------------------------------------------------
     | 3. Traveler acknowledges assignment → approval chain starts
     | ---------------------------------------------------------------*/

    public function test_assigned_traveler_can_acknowledge(): void
    {
        $tr = $this->makeAssignment();

        $this->actingAs($this->traveler)
            ->post(route('assignments.acknowledge', $tr))
            ->assertRedirect(route('travel-requests.show', $tr));

        $tr->refresh();

        $this->assertSame('pending', $tr->status);
        $this->assertNotNull($tr->acknowledged_at);
        $this->assertNotNull($tr->submitted_at);

        // Level-1 approval row created with first approver
        $this->assertDatabaseHas('approvals', [
            'travel_request_id' => $tr->id,
            'level'             => 1,
            'approver_id'       => $this->approver->id,
            'action'            => 'pending',
        ]);
    }

    public function test_other_user_cannot_acknowledge_someone_elses_assignment(): void
    {
        $tr = $this->makeAssignment();

        $this->actingAs($this->otherTraveler)
            ->post(route('assignments.acknowledge', $tr))
            ->assertForbidden();

        $tr->refresh();
        $this->assertNull($tr->acknowledged_at);
        $this->assertSame('assigned', $tr->status);
    }

    public function test_acknowledge_is_idempotent_noop_after_already_acknowledged(): void
    {
        $tr = $this->makeAssignment();

        // First ack: success
        $this->actingAs($this->traveler)
            ->post(route('assignments.acknowledge', $tr))
            ->assertRedirect(route('travel-requests.show', $tr));

        $approvalCountAfterFirst = Approval::where('travel_request_id', $tr->id)->count();

        // Second ack: blocked (needsAcknowledgement is now false)
        $this->actingAs($this->traveler)
            ->post(route('assignments.acknowledge', $tr))
            ->assertSessionHas('error');

        $this->assertSame(
            $approvalCountAfterFirst,
            Approval::where('travel_request_id', $tr->id)->count(),
            'Approval rows should not multiply on re-acknowledge.'
        );
    }

    /* -----------------------------------------------------------------
     | 4. Traveler declines assignment
     | ---------------------------------------------------------------*/

    public function test_assigned_traveler_can_decline(): void
    {
        $tr = $this->makeAssignment();

        $this->actingAs($this->traveler)
            ->post(route('assignments.decline', $tr))
            ->assertRedirect(route('dashboard'));

        $tr->refresh();

        $this->assertSame('declined', $tr->status);
        $this->assertNull($tr->acknowledged_at);

        // No approval rows created
        $this->assertDatabaseMissing('approvals', [
            'travel_request_id' => $tr->id,
        ]);
    }

    public function test_other_user_cannot_decline_someone_elses_assignment(): void
    {
        $tr = $this->makeAssignment();

        $this->actingAs($this->otherTraveler)
            ->post(route('assignments.decline', $tr))
            ->assertForbidden();

        $tr->refresh();
        $this->assertSame('assigned', $tr->status);
    }

    public function test_cannot_decline_after_acknowledged(): void
    {
        $tr = $this->makeAssignment();

        // Acknowledge first
        $this->actingAs($this->traveler)
            ->post(route('assignments.acknowledge', $tr));

        // Try to decline after ack
        $this->actingAs($this->traveler)
            ->post(route('assignments.decline', $tr))
            ->assertSessionHas('error');

        $tr->refresh();
        $this->assertSame('pending', $tr->status);
    }

    /* -----------------------------------------------------------------
     | 5. Index list only shows the assigner's own assignments
     | ---------------------------------------------------------------*/

    public function test_assignment_index_only_shows_current_users_assignments(): void
    {
        // Approver assigns one
        $own = $this->makeAssignment();

        // Admin assigns another
        $foreign = TravelRequest::create([
            'request_no'     => 'TR-ADMIN-01',
            'user_id'        => $this->traveler->id,
            'department_id'  => $this->ccis->id,
            'destination'    => 'Cebu',
            'purpose'        => 'Workshop',
            'date_from'      => now()->addDays(5),
            'date_to'        => now()->addDays(7),
            'estimated_cost' => 2000,
            'status'         => 'assigned',
            'type'           => 'assigned',
            'assigned_by'    => $this->admin->id,
        ]);

        $this->actingAs($this->approver)
            ->get(route('assignments.index'))
            ->assertOk()
            ->assertSee($own->destination)
            ->assertDontSee($foreign->destination);
    }

    /* -----------------------------------------------------------------
     | 6. Model helpers sanity
     | ---------------------------------------------------------------*/

    public function test_needs_acknowledgement_helper_state_machine(): void
    {
        $tr = $this->makeAssignment();
        $this->assertTrue($tr->needsAcknowledgement());

        $tr->update(['acknowledged_at' => now(), 'status' => 'pending']);
        $this->assertFalse($tr->fresh()->needsAcknowledgement());

        // Self-request (not an assignment) never needs ack
        $self = TravelRequest::create([
            'request_no'     => 'TR-SELF-01',
            'user_id'        => $this->traveler->id,
            'department_id'  => $this->ccis->id,
            'destination'    => 'Boracay',
            'purpose'        => 'Research',
            'date_from'      => now()->addWeek(),
            'date_to'        => now()->addWeek()->addDay(),
            'estimated_cost' => 3000,
            'status'         => 'pending',
            'type'           => 'self',
        ]);
        $this->assertFalse($self->needsAcknowledgement());
    }

    public function test_assigner_relationship_loads_correctly(): void
    {
        $tr = $this->makeAssignment();

        $this->assertInstanceOf(User::class, $tr->assigner);
        $this->assertSame($this->approver->id, $tr->assigner->id);
        $this->assertSame('Approver', $tr->assigner->name);
    }

    /* -----------------------------------------------------------------
     | Helpers
     | ---------------------------------------------------------------*/

    private function makeAssignment(): TravelRequest
    {
        return TravelRequest::create([
            'request_no'     => 'TR-TEST-' . uniqid(),
            'user_id'        => $this->traveler->id,
            'department_id'  => $this->ccis->id,
            'destination'    => 'Manila',
            'purpose'        => 'Conference',
            'date_from'      => now()->addWeek(),
            'date_to'        => now()->addWeek()->addDays(2),
            'estimated_cost' => 5000,
            'status'         => 'assigned',
            'type'           => 'assigned',
            'assigned_by'    => $this->approver->id,
        ]);
    }
}
