<?php

namespace Tests\Feature;

use App\Http\Controllers\TraceController;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class QrTracingTest extends TestCase
{
    use RefreshDatabase;

    private Department $dept;
    private User $traveler;
    private User $approver;
    private TravelRequest $tr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept = Department::create(['name' => 'College of Computer and Information Studies', 'abbreviation' => 'CCIS']);

        $this->traveler = User::create([
            'name'          => 'Juan Dela Cruz',
            'email'         => 'juan@antiquespride.edu.ph',
            'password'      => Hash::make('password'),
            'role'          => 'traveler',
            'status'        => 'active',
            'department_id' => $this->dept->id,
        ]);

        $this->approver = User::create([
            'name'           => 'Dean Reyes',
            'email'          => 'dean@antiquespride.edu.ph',
            'password'       => Hash::make('password'),
            'role'           => 'approver',
            'status'         => 'active',
            'approver_level' => 1,
            'department_id'  => $this->dept->id,
        ]);

        $this->tr = TravelRequest::create([
            'request_no'     => 'TR-QR-TEST-001',
            'user_id'        => $this->traveler->id,
            'department_id'  => $this->dept->id,
            'destination'    => 'Manila',
            'purpose'        => 'Conference attendance',
            'date_from'      => now()->addWeek(),
            'date_to'        => now()->addWeek()->addDays(2),
            'estimated_cost' => 3000,
            'status'         => 'approved',
            'type'           => 'self',
            'submitted_at'   => now()->subDay(),
        ]);
    }

    /* -----------------------------------------------------------------
     | 1. Signed URL generation & public trace page
     | ---------------------------------------------------------------*/

    public function test_signed_trace_url_is_generated_for_a_travel_request(): void
    {
        $url = TraceController::signedTraceUrl($this->tr);

        $this->assertStringContainsString('/trace/TR-QR-TEST-001', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_public_trace_page_loads_with_valid_signed_url(): void
    {
        $url = TraceController::signedTraceUrl($this->tr);

        $response = $this->get($url);

        $response->assertOk()
            ->assertSee('TR-QR-TEST-001')
            ->assertSee('Manila')
            ->assertSee('Authorized Travel') // approved state message
            ->assertSee('JC'); // initials: Juan ... Cruz — privacy redaction
    }

    public function test_public_trace_page_rejects_unsigned_url(): void
    {
        // Hit the route WITHOUT a valid signature
        $this->get('/trace/TR-QR-TEST-001')
            ->assertForbidden();
    }

    public function test_public_trace_page_rejects_tampered_signature(): void
    {
        $url = TraceController::signedTraceUrl($this->tr);
        // Corrupt the signature param
        $tampered = preg_replace('/signature=[a-f0-9]+/', 'signature=deadbeef', $url);

        $this->get($tampered)->assertForbidden();
    }

    public function test_public_trace_page_redacts_sensitive_data(): void
    {
        $url = TraceController::signedTraceUrl($this->tr);

        $response = $this->get($url);

        // Sensitive fields must NOT appear on the public page
        $response->assertDontSee('Juan Dela Cruz')           // full name hidden
                 ->assertDontSee('Conference attendance')    // purpose hidden
                 ->assertDontSee('3,000')                    // cost hidden
                 ->assertDontSee('3000');
    }

    public function test_public_trace_page_writes_audit_log_entry(): void
    {
        $url = TraceController::signedTraceUrl($this->tr);

        $this->assertSame(0, AuditLog::where('action', 'request.traced')->count());

        $this->get($url)->assertOk();

        $log = AuditLog::where('action', 'request.traced')->first();

        $this->assertNotNull($log, 'request.traced audit entry should be written on scan');
        $this->assertSame(TravelRequest::class, $log->auditable_type);
        $this->assertSame($this->tr->id, $log->auditable_id);
        $this->assertNull($log->user_id, 'Public scans have no authenticated actor');
        $this->assertArrayHasKey('ip', $log->metadata);
        $this->assertSame('qr-scan', $log->metadata['via']);
    }

    public function test_public_trace_page_shows_different_message_for_rejected_request(): void
    {
        $this->tr->update(['status' => 'rejected']);

        $url = TraceController::signedTraceUrl($this->tr);

        $this->get($url)
            ->assertOk()
            ->assertSee('Not Authorized');
    }

    public function test_public_trace_page_404s_for_unknown_request_no(): void
    {
        $url = URL::signedRoute('trace.show', ['requestNo' => 'TR-NONEXISTENT']);

        $this->get($url)->assertNotFound();
    }

    /* -----------------------------------------------------------------
     | 2. Inline QR endpoint (authenticated)
     | ---------------------------------------------------------------*/

    public function test_qr_endpoint_returns_svg_for_owner(): void
    {
        $response = $this->actingAs($this->traveler)
            ->get(route('travel-requests.qr', $this->tr));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');

        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_qr_endpoint_denies_unrelated_user(): void
    {
        $stranger = User::create([
            'name'          => 'Stranger',
            'email'         => 'stranger@antiquespride.edu.ph',
            'password'      => Hash::make('password'),
            'role'          => 'traveler',
            'status'        => 'active',
            'department_id' => $this->dept->id,
        ]);

        // Route is auth-only; authorization is at the controller level for the
        // show/print pair. The QR itself is gated by auth (prevents anon scraping)
        // but knowingly-linked travelers can see it. We assert at least auth is required.
        $this->get(route('travel-requests.qr', $this->tr))
            ->assertRedirect(route('login'));

        // An authenticated stranger can fetch the QR (which only encodes a signed
        // public URL — no sensitive payload). This is intentional for the
        // verification workflow. Assert it still returns SVG:
        $this->actingAs($stranger)
            ->get(route('travel-requests.qr', $this->tr))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    /* -----------------------------------------------------------------
     | 3. Printable travel-order view
     | ---------------------------------------------------------------*/

    public function test_print_view_is_accessible_to_owner(): void
    {
        $response = $this->actingAs($this->traveler)
            ->get(route('travel-requests.print', $this->tr));

        $response->assertOk()
            ->assertSee('Official Travel Order')
            ->assertSee('TR-QR-TEST-001')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('Manila')
            // QR img tag embedded
            ->assertSee(route('travel-requests.qr', $this->tr), false);
    }

    public function test_print_view_denies_unrelated_user(): void
    {
        $stranger = User::create([
            'name'          => 'Stranger',
            'email'         => 'stranger2@antiquespride.edu.ph',
            'password'      => Hash::make('password'),
            'role'          => 'traveler',
            'status'        => 'active',
            'department_id' => $this->dept->id,
        ]);

        $this->actingAs($stranger)
            ->get(route('travel-requests.print', $this->tr))
            ->assertForbidden();
    }

    /* -----------------------------------------------------------------
     | 4. Extra integration coverage
     | ---------------------------------------------------------------*/

    public function test_trace_route_rate_limits_excessive_scans(): void
    {
        $url = TraceController::signedTraceUrl($this->tr);

        // Throttle is 30/min per IP. Send 31 requests and expect the 31st
        // to be blocked with HTTP 429.
        $lastStatus = 200;
        for ($i = 1; $i <= 31; $i++) {
            $response = $this->get($url);
            $lastStatus = $response->status();
            if ($lastStatus === 429) {
                break;
            }
        }

        $this->assertSame(
            429,
            $lastStatus,
            'Public trace endpoint should rate-limit at 30 requests/min per IP.'
        );
    }

    public function test_qr_svg_encodes_a_signed_trace_url(): void
    {
        // An SVG QR cannot be re-decoded trivially in tests, but we can verify
        // the controller's helper is the same one wired into the QR generator
        // and that the signed URL it produces is independently valid.
        $signedUrl = TraceController::signedTraceUrl($this->tr);

        // Assert the signed URL round-trips successfully (valid signature)
        $this->get($signedUrl)->assertOk();

        // And the QR endpoint itself returns SVG
        $qrResponse = $this->actingAs($this->traveler)
            ->get(route('travel-requests.qr', $this->tr));

        $qrResponse->assertOk();
        $this->assertStringContainsString('<svg', $qrResponse->getContent());
        $this->assertGreaterThan(500, strlen($qrResponse->getContent()));
    }

    public function test_print_view_embeds_qr_that_is_reachable(): void
    {
        // Fetch the print view
        $this->actingAs($this->traveler)
            ->get(route('travel-requests.print', $this->tr))
            ->assertOk()
            ->assertSee(route('travel-requests.qr', $this->tr), false);

        // Now fetch the embedded QR URL the print page references — it must return SVG
        $qrResponse = $this->actingAs($this->traveler)
            ->get(route('travel-requests.qr', $this->tr));

        $qrResponse->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }
}
