<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\User;
use App\Models\WorkflowHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_college_personnel_role_is_normalized_to_coordinator()
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'College Personnel'])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SenderDashboard')
                ->has('stats')
                ->has('analytics')
            );
    }

    public function test_college_personnel_user_sees_sender_dashboard()
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'College Personnel'])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SenderDashboard')
            );
    }

    public function test_viewer_does_not_see_authorized_personnel_actions()
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'viewer'])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Upload your draft or submit an agreement')
            ->assertDontSee('Upload Draft')
            ->assertDontSee('Submit Agreement');
    }

    public function test_admin_dashboard_includes_reports_and_analytics()
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        Agreement::create([
            'title' => 'Active Agreement',
            'type' => 'MOA',
            'partner_organization' => 'Alpha University',
            'status' => 'active',
            'workflow_status' => 'active_agreement',
            'signed_at' => now(),
            'expires_at' => now()->addDays(20),
            'submitted_by' => $admin->id,
        ]);

        Agreement::create([
            'title' => 'Renewed Agreement',
            'type' => 'MOU',
            'partner_organization' => 'Alpha University',
            'status' => 'expired',
            'workflow_status' => null,
            'signed_at' => now()->subYear(),
            'expires_at' => now()->addDays(200),
            'submitted_by' => $admin->id,
        ]);

        Agreement::create([
            'title' => 'Expired Agreement',
            'type' => 'MOA',
            'partner_organization' => 'Beta College',
            'status' => 'expired',
            'workflow_status' => 'active_agreement',
            'signed_at' => now()->subYear(),
            'expires_at' => now()->subDays(5),
            'submitted_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminDashboard')
                ->has('analytics')
                ->has('analytics.partnerPerformance')
            );
    }

    public function test_admin_dashboard_excludes_unapproved_agreements_from_partner_analytics()
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        Agreement::create([
            'title' => 'Approved Agreement',
            'type' => 'MOA',
            'partner_organization' => 'Approved University',
            'status' => 'active',
            'workflow_status' => 'active_agreement',
            'signed_at' => now(),
            'expires_at' => now()->addDays(30),
            'submitted_by' => $admin->id,
        ]);

        Agreement::create([
            'title' => 'Pending Agreement',
            'type' => 'MOU',
            'partner_organization' => 'Pending Institute',
            'status' => 'active',
            'workflow_status' => 'legal_assistant_iii',
            'signed_at' => now(),
            'expires_at' => now()->addDays(30),
            'submitted_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminDashboard')
                ->where('analytics.activePartnerships', 1)
                ->where('analytics.partnerCount', 1)
                ->where('analytics.partnerPerformance.0.partner_organization', 'Approved University')
                ->where('analytics.partnerPerformance.0.total_partnerships', 1)
            );
    }

    public function test_coordinator_dashboard_includes_reports_and_analytics()
    {
        $coordinator = User::factory()->create();
        $coordinator->forceFill(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_ii'])->save();

        Agreement::create([
            'title' => 'Coordinator Active Agreement',
            'type' => 'MOU',
            'partner_organization' => 'Gamma Institute',
            'status' => 'active',
            'workflow_status' => 'legal_assistant_ii',
            'signed_at' => now(),
            'expires_at' => now()->addDays(10),
            'submitted_by' => $coordinator->id,
        ]);

        $this->actingAs($coordinator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('LegalIICoordinatorDashboard')
                ->has('analytics')
                ->has('analytics.partnerPerformance')
            );
    }

    public function test_role_dashboard_includes_repository_monitoring_and_audit_versions()
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'authorized_personnel'])->save();

        Agreement::create([
            'title' => 'Repository Test Agreement',
            'type' => 'MOU',
            'partner_organization' => 'Repository University',
            'status' => 'active',
            'workflow_status' => 'active_agreement',
            'signed_at' => now(),
            'expires_at' => now()->addDays(15),
            'submitted_by' => $user->id,
        ]);

        WorkflowHistory::create([
            'agreement_id' => 1,
            'action' => 'Agreement approved',
            'performed_by' => $user->id,
            'from_status' => 'legal_assistant_iii',
            'to_status' => 'active_agreement',
        ]);

        AgreementVersion::create([
            'agreement_id' => 1,
            'version' => '1.0',
            'document' => 'repository-test.pdf',
            'uploaded_by' => $user->name,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AuthorizedPersonnelDashboard')
                ->has('analytics')
                ->has('myAgreements')
                ->has('stats')
                ->has('inReview')
                ->has('active')
                ->has('drafts')
            );
    }

    public function test_workflow_dashboard_renders_all_stage_columns()
    {
        $coordinator = User::factory()->create();
        $coordinator->forceFill(['role' => 'coordinator'])->save();

        Agreement::create([
            'title' => 'Workflow Dashboard Agreement',
            'type' => 'MOA',
            'partner_organization' => 'Workflow Partner',
            'status' => 'for_review',
            'workflow_status' => 'legal_assistant_ii',
            'submitted_by' => $coordinator->id,
        ]);

        $this->actingAs($coordinator)
            ->get('/workflow-dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CoordinatorWorkflowDashboard')
                ->has('legalAssistantII')
                ->has('legalAssistantIII')
                ->has('attorneyReview')
                ->has('adminLogging')
                ->has('attorneyInitials')
                ->has('presidentApproval')
                ->has('activeAgreements')
            );
    }
}
