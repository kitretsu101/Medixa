<?php

namespace Tests\Feature;

use App\Models\ConsentToken;
use App\Models\LabReport;
use App\Models\User;
use App\Services\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_with_valid_token_can_access_lab_report_and_creates_one_audit_log_row(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $labReport = LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'report_title' => 'Complete Blood Count',
        ]);

        $consentToken = ConsentToken::factory()->create([
            'patient_id' => $patient->id,
            'grantee_id' => $doctor->id,
            'resource_type' => 'lab_report',
            'resource_id' => $labReport->id,
            'expires_at' => now()->addHour(),
            'revoked_at' => null,
        ]);

        $this->assertDatabaseCount('audit_log', 0);

        $response = $this->actingAs($doctor)->get(route('doctor.lab-reports.show', $labReport));

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.id', $labReport->id);

        $this->assertDatabaseCount('audit_log', 1);
        $this->assertDatabaseHas('audit_log', [
            'actor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'resource_type' => 'lab_report',
            'resource_id' => $labReport->id,
            'consent_token_id' => $consentToken->id,
        ]);
    }

    public function test_doctor_with_expired_token_gets_403_and_zero_audit_log_rows(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $labReport = LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        ConsentToken::factory()->expired()->create([
            'patient_id' => $patient->id,
            'grantee_id' => $doctor->id,
            'resource_type' => 'lab_report',
            'resource_id' => $labReport->id,
        ]);

        $response = $this->actingAs($doctor)->get(route('doctor.lab-reports.show', $labReport));

        $response->assertForbidden();
        $this->assertDatabaseCount('audit_log', 0);
    }

    public function test_doctor_with_revoked_token_gets_403_and_zero_audit_log_rows(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $labReport = LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        ConsentToken::factory()->revoked()->create([
            'patient_id' => $patient->id,
            'grantee_id' => $doctor->id,
            'resource_type' => 'lab_report',
            'resource_id' => $labReport->id,
        ]);

        $response = $this->actingAs($doctor)->get(route('doctor.lab-reports.show', $labReport));

        $response->assertForbidden();
        $this->assertDatabaseCount('audit_log', 0);
    }

    public function test_doctor_with_no_token_gets_403_and_zero_audit_log_rows(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $labReport = LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->actingAs($doctor)->get(route('doctor.lab-reports.show', $labReport));

        $response->assertForbidden();
        $this->assertDatabaseCount('audit_log', 0);
    }

    public function test_patient_accessing_own_resource_bypasses_consent_check_without_audit_log(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $labReport = LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->actingAs($patient)->get(route('patient.lab-reports.show', $labReport));

        $response->assertOk();
        $response->assertJsonPath('data.id', $labReport->id);
        // Patient accessing their own record is not an external consent-delegated read
        $this->assertDatabaseCount('audit_log', 0);
    }

    public function test_role_middleware_blocks_access_to_mismatched_roles(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        // Patient attempting to visit doctor dashboard
        $response = $this->actingAs($patient)->get(route('doctor.dashboard'));
        $response->assertForbidden();

        // Doctor attempting to visit patient dashboard
        $response = $this->actingAs($doctor)->get(route('patient.dashboard'));
        $response->assertForbidden();
    }

    public function test_consent_service_issue_validate_revoke_and_active_tokens(): void
    {
        $service = app(ConsentService::class);

        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        // Issue token
        $token = $service->issue(
            patientId: $patient->id,
            granteeId: $doctor->id,
            resourceType: 'lab_report',
            resourceId: 99,
            ttlMinutes: 30,
        );

        $this->assertTrue($service->validate($token->id));
        $this->assertCount(1, $service->activeTokensForPatient($patient->id));

        // Revoke token
        $this->assertTrue($service->revoke($token->id));
        $this->assertFalse($service->validate($token->id));
        $this->assertCount(0, $service->activeTokensForPatient($patient->id));
    }
}
