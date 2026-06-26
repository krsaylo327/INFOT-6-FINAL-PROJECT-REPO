<?php

namespace Tests\Browser;

use App\Models\Agreement;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AgreementWorkflowBrowserTest extends DuskTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Ensure the environment is migrated for browser tests
        Artisan::call('migrate:fresh');
    }

    public function test_authorized_personnel_sees_create_button()
    {
        $user = User::factory()->create(['role' => 'authorized_personnel']);

        $agreement = Agreement::factory()->create([
            'workflow_status' => 'active_agreement',
        ]);

        $this->browse(function (Browser $browser) use ($user, $agreement) {
            $browser->loginAs($user)
                ->visit("/agreements/{$agreement->id}")
                ->assertSee('Create Agreement');
        });
    }

    public function test_legal_assistant_ii_sees_forward_button_but_not_mark_active()
    {
        $user = User::factory()->create(['role' => 'legal_assistant_ii']);

        $agreement = Agreement::factory()->create([
            'workflow_status' => 'legal_assistant_ii',
        ]);

        $this->browse(function (Browser $browser) use ($user, $agreement) {
            $browser->loginAs($user)
                ->visit("/agreements/{$agreement->id}")
                ->assertSee('Forward to Legal Assistant III')
                ->assertDontSee('Mark Active Agreement');
        });
    }

    public function test_president_sees_approve_button()
    {
        $user = User::factory()->create(['role' => 'coordinator', 'coordinator_stage' => 'president_approval']);

        $agreement = Agreement::factory()->create([
            'workflow_status' => 'president_approval',
        ]);

        $this->browse(function (Browser $browser) use ($user, $agreement) {
            $browser->loginAs($user)
                ->visit("/agreements/{$agreement->id}")
                ->assertSee('Approve — Mark Active');
        });
    }
}
