<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgreementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_forwarding_updates_workflow_and_creates_history()
    {
        $legalII = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_ii']);

        $agreement = Agreement::create([
            'title' => 'Test Agreement',
            'type' => 'MOA',
            'partner_organization' => 'Partner X',
            'status' => 'for_review',
            'workflow_status' => 'legal_assistant_ii',
            'current_handler' => 'Legal Assistant II',
            'submitted_by' => 1,
        ]);

        $response = $this->actingAs($legalII)->post("/agreements/{$agreement->id}/forward", [
            'next_status' => 'legal_assistant_iii',
            'remarks' => 'Moving to LA III',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('agreements', [
            'id' => $agreement->id,
            'workflow_status' => 'legal_assistant_iii',
            'current_handler' => 'Legal Assistant III',
        ]);

        $this->assertDatabaseHas('workflow_histories', [
            'agreement_id' => $agreement->id,
            'to_status' => 'legal_assistant_iii',
            'performed_by' => $legalII->name,
        ]);
    }

    public function test_updating_document_creates_version()
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => null]);

        // Attach an initial document and create via POST
        $file = UploadedFile::fake()->create('initial.pdf', 100, 'application/pdf');

        $responseCreate = $this->actingAs($user)->post('/agreements', [
            'title' => 'Versioned Agreement',
            'type' => 'MOU',
            'partner_organization' => 'Partner Y',
            'document' => $file,
        ]);

        $responseCreate->assertStatus(302);

        // Retrieve the created agreement record
        $created = Agreement::where('title', 'Versioned Agreement')->latest()->first();

        $this->assertNotNull($created);
        $this->assertStringStartsWith("agreements/{$created->id}/current/", (string) $created->document);
        $this->assertSame('initial.pdf', basename($created->document));
        // Now update with a new document; this should snapshot the previous one
        $newFile = UploadedFile::fake()->create('revised.pdf', 120, 'application/pdf');

        $responseUpdate = $this->actingAs($user)->put("/agreements/{$created->id}", [
            'title' => $created->title,
            'type' => $created->type,
            'partner_organization' => $created->partner_organization,
            'document' => $newFile,
            'status' => $created->status,
        ]);

        $responseUpdate->assertStatus(302);

        $created->refresh();

        $this->assertStringStartsWith("agreements/{$created->id}/current/", (string) $created->document);
        $this->assertSame('revised.pdf', basename($created->document));
        Storage::disk('public')->assertExists($created->document);

        $versions = AgreementVersion::where('agreement_id', $created->id)->orderBy('id')->get();
        $this->assertCount(2, $versions);
        $this->assertStringStartsWith("agreements/{$created->id}/versions/v1/", (string) $versions[0]->document);
        $this->assertStringStartsWith("agreements/{$created->id}/versions/v2/", (string) $versions[1]->document);
        $this->assertSame('initial.pdf', basename($versions[0]->document));
        $this->assertSame('revised.pdf', basename($versions[1]->document));

        // There should be at least one AgreementVersion entry for this agreement
        $this->assertDatabaseHas('agreement_versions', [
            'agreement_id' => $created->id,
        ]);
    }

    public function test_agreement_becomes_active_after_full_workflow()
    {
        Storage::fake('public');

        $sender = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => null]);
        $la2 = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_ii']);
        $la3 = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_iii']);
        $attorney = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'attorney']);
        $adminAid = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'administrative_aid']);
        $president = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'president_approval']);

        $file = UploadedFile::fake()->create('agreement.pdf', 120, 'application/pdf');

        // Sender creates agreement
        $this->actingAs($sender)->post('/agreements', [
            'title' => 'Full Workflow Agreement',
            'type' => 'MOA',
            'partner_organization' => 'Full Workflow Partner',
            'document' => $file,
        ])->assertRedirect();

        $agreement = Agreement::where('title', 'Full Workflow Agreement')->latest()->first();
        $this->assertNotNull($agreement);
        $this->assertSame('for_review', $agreement->status);

        // Forward through all stages
        $this->actingAs($la2)->post("/agreements/{$agreement->id}/forward", [
            'next_status' => 'legal_assistant_iii',
        ])->assertStatus(302);

        $this->actingAs($la3)->post("/agreements/{$agreement->id}/forward", [
            'next_status' => 'attorney_review',
        ])->assertStatus(302);

        $this->actingAs($attorney)->post("/agreements/{$agreement->id}/forward", [
            'next_status' => 'administrative_aid',
        ])->assertStatus(302);

        // Admin Aid sends back to attorney_initials (return, not forward)
        $this->actingAs($adminAid)->post("/agreements/{$agreement->id}/return", [
            'remarks' => 'Logged and processed',
            'return_to' => 'attorney_initials',
        ])->assertStatus(302);

        $this->actingAs($attorney)->post("/agreements/{$agreement->id}/forward", [
            'next_status' => 'president_approval',
        ])->assertStatus(302);

        $this->actingAs($president)->post("/agreements/{$agreement->id}/forward", [
            'next_status' => 'active_agreement',
        ])->assertStatus(302);

        $agreement->refresh();
        $this->assertSame('active', $agreement->status);
        $this->assertSame('active_agreement', $agreement->workflow_status);
    }

    public function test_college_personnel_can_access_agreement_creation_routes()
    {
        $collegePersonnel = User::factory()->create(['role' => 'College Personnel']);

        $this->actingAs($collegePersonnel)
            ->get('/agreements/create?mode=upload')
            ->assertOk();

        $this->actingAs($collegePersonnel)
            ->get('/agreements/create?mode=create')
            ->assertOk();
    }

    public function test_college_personnel_can_submit_an_agreement_without_403()
    {
        $collegePersonnel = User::factory()->create(['role' => 'College Personnel']);

        $response = $this->actingAs($collegePersonnel)->post('/agreements', [
            'title' => 'College Personnel Submitted Agreement',
            'type' => 'MOA',
            'partner_organization' => 'College Partner',
            'description' => 'Submitted by College Personnel',
            'status' => 'for_review',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('agreements', [
            'title' => 'College Personnel Submitted Agreement',
            'partner_organization' => 'College Partner',
            'status' => 'for_review',
        ]);
    }

    public function test_attorney_returns_agreement_from_president_to_attorney_initials()
    {
        $attorney = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'attorney']);

        $agreement = Agreement::create([
            'title' => 'Final Review Agreement',
            'type' => 'MOU',
            'partner_organization' => 'Partner Z',
            'status' => 'for_review',
            'workflow_status' => 'president_approval',
            'current_handler' => 'President',
            'submitted_by' => 1,
        ]);

        $this->actingAs($attorney)->post("/agreements/{$agreement->id}/return", [
            'remarks' => 'Send back for initials revision',
        ])->assertStatus(302);

        $this->assertDatabaseHas('agreements', [
            'id' => $agreement->id,
            'workflow_status' => 'attorney_initials',
        ]);
    }

    public function test_legal_assistant_ii_returns_agreement_to_sender()
    {
        $lai = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_ii']);
        $sender = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => null]);

        $agreement = Agreement::create([
            'title' => 'Return Test Agreement',
            'type' => 'MOU',
            'partner_organization' => 'Test Partner',
            'status' => 'for_review',
            'workflow_status' => 'legal_assistant_ii',
            'current_handler' => 'Legal Assistant II',
            'submitted_by' => $sender->id,
        ]);

        $this->actingAs($lai)->post("/agreements/{$agreement->id}/return", [
            'remarks' => 'Please revise the terms',
        ])->assertStatus(302);

        $agreement->refresh();
        $this->assertEquals('draft', $agreement->workflow_status);
        $this->assertEquals($sender->name, $agreement->current_handler);

        $this->assertDatabaseHas('workflow_histories', [
            'agreement_id' => $agreement->id,
            'action' => 'Returned',
            'from_status' => 'legal_assistant_ii',
            'to_status' => 'draft',
            'performed_by' => $lai->name,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $sender->id,
            'title' => 'MOU returned for revision',
        ]);
    }

    public function test_sender_can_save_multiple_drafts_and_view_them_in_drafts_page()
    {
        $sender = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => null]);
        $otherUser = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => null]);

        $this->actingAs($sender)->post('/agreements', [
            'title' => 'Draft Agreement One',
            'type' => 'MOA',
            'partner_organization' => 'Partner Draft One',
            'status' => 'draft',
        ])->assertRedirect('/agreements');

        $this->actingAs($sender)->post('/agreements', [
            'title' => 'Draft Agreement Two',
            'type' => 'MOU',
            'partner_organization' => 'Partner Draft Two',
            'status' => 'draft',
        ])->assertRedirect('/agreements');

        Agreement::create([
            'title' => 'Someone Else Draft',
            'type' => 'MOA',
            'partner_organization' => 'Other Partner',
            'status' => 'draft',
            'workflow_status' => 'draft',
            'current_handler' => $otherUser->name,
            'submitted_by' => $otherUser->id,
        ]);

        $this->assertSame(2, Agreement::where('submitted_by', $sender->id)->where('status', 'draft')->count());

        $this->actingAs($sender)
            ->get('/agreements?filter=drafts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Agreements')
                ->has('agreements', 3)
            );
    }

    public function test_non_authorized_users_cannot_access_draft_agreements_filter()
    {
        $unauthorized = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($unauthorized)
            ->get('/agreements?filter=drafts')
            ->assertForbidden();
    }

    public function test_uploaded_documents_are_saved_as_user_versions()
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'legal_assistant_ii']);

        $agreement = Agreement::create([
            'title' => 'Versioned Agreement',
            'type' => 'MOU',
            'partner_organization' => 'Version Partner',
            'status' => 'for_review',
            'workflow_status' => 'legal_assistant_ii',
            'current_handler' => 'Legal Assistant II',
            'submitted_by' => $user->id,
        ]);

        $firstUpload = UploadedFile::fake()->create('first_upload.pdf', 100);
        $this->actingAs($user)
            ->put("/agreements/{$agreement->id}", [
                'status' => 'for_review',
                'document' => $firstUpload,
            ])
            ->assertStatus(302);

        $agreement->refresh();

        $secondUpload = UploadedFile::fake()->create('second_upload.pdf', 200);
        $this->actingAs($user)
            ->put("/agreements/{$agreement->id}", [
                'status' => 'for_review',
                'document' => $secondUpload,
            ])
            ->assertStatus(302);

        $agreement->refresh();

        $versions = $agreement->versions()->orderBy('id')->get();

        $this->assertCount(2, $versions);
        $this->assertSame('v1', $versions[0]->version);
        $this->assertSame('v2', $versions[1]->version);
        $this->assertSame($user->id, $versions[1]->uploaded_by_id);
        $this->assertTrue(Storage::disk('public')->exists($versions[0]->document));
        $this->assertTrue(Storage::disk('public')->exists($versions[1]->document));
        $this->assertStringContainsString('/v2/', $versions[1]->document);
        $this->assertSame('first_upload.pdf', basename($versions[0]->document));
        $this->assertSame('second_upload.pdf', basename($versions[1]->document));
    }

    public function test_users_cannot_view_other_users_draft_agreements()
    {
        $owner = User::factory()->create(['role' => 'authorized_personnel', 'organization_id' => 1]);
        $otherUser = User::factory()->create(['role' => 'authorized_personnel', 'organization_id' => 2]);

        $draft = Agreement::create([
            'title' => 'Private Draft',
            'type' => 'MOA',
            'partner_organization' => 'Private Partner',
            'status' => 'draft',
            'workflow_status' => 'draft',
            'current_handler' => $owner->name,
            'submitted_by' => $owner->id,
        ]);

        $this->actingAs($otherUser)
            ->get("/agreements/{$draft->id}")
            ->assertForbidden();

        $this->actingAs($owner)
            ->get("/agreements/{$draft->id}")
            ->assertOk();
    }

    public function test_admin_can_view_draft_agreements()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $draft = Agreement::create([
            'title' => 'Admin-visible Draft',
            'type' => 'MOA',
            'partner_organization' => 'Admin Partner',
            'status' => 'draft',
            'workflow_status' => 'draft',
            'current_handler' => 'Admin',
            'submitted_by' => 1,
        ]);

        $this->actingAs($admin)
            ->get("/agreements/{$draft->id}")
            ->assertOk();
    }
}
