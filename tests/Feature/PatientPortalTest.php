<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ConsentToken;
use App\Models\LabReport;
use App\Models\Prescription;
use App\Models\User;
use App\Services\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PatientPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('patient.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_doctor_cannot_access_patient_portal(): void
    {
        $doctor = User::factory()->doctor()->create();

        $response = $this->actingAs($doctor)->get(route('patient.dashboard'));
        $response->assertForbidden();
    }

    public function test_patient_can_view_dashboard_with_stats(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->get(route('patient.dashboard'));

        $response->assertOk();
        $response->assertViewIs('patient.dashboard');
        $response->assertViewHas('prescriptionCount', 0);
        $response->assertViewHas('labReportCount', 0);
        $response->assertViewHas('activeConsentsCount', 0);
    }

    public function test_patient_can_view_and_update_profile(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->get(route('patient.profile'));
        $response->assertOk();
        $response->assertViewIs('patient.profile');

        $updateResponse = $this->actingAs($patient)->put(route('patient.profile.update'), [
            'dob' => '1990-05-15',
            'blood_group' => 'O+',
            'emergency_contact' => '+1-555-0199',
        ]);

        $updateResponse->assertRedirect(route('patient.profile'));
        $updateResponse->assertSessionHas('status', 'Profile updated successfully.');

        $this->assertDatabaseHas('patient_profiles', [
            'user_id' => $patient->id,
            'blood_group' => 'O+',
            'emergency_contact' => '+1-555-0199',
        ]);
    }

    public function test_patient_can_view_merged_record_timeline(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->toDateString(),
            'serial_number' => 1,
            'status' => 'completed',
        ]);

        // Create prescription
        $prescription = Prescription::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Seasonal Allergies',
            'medicines' => [['name' => 'Cetirizine', 'dosage' => '10mg', 'frequency' => 'Once daily']],
            'instructions' => 'Take with water at night.',
            'created_at' => now()->subDays(2),
        ]);

        // Create lab report
        $labReport = LabReport::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'report_title' => 'Lipid Panel',
            'file_path' => 'reports/sample.pdf',
            'review_status' => 'reviewed',
            'uploaded_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($patient)->get(route('patient.timeline'));

        $response->assertOk();
        $response->assertViewIs('patient.timeline');
        $response->assertSee('Seasonal Allergies');
        $response->assertSee('Lipid Panel');
        $response->assertSee('Cetirizine');
    }

    public function test_patient_can_view_consent_center_and_revoke_token(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $consentService = app(ConsentService::class);

        $token = $consentService->issue(
            patientId: $patient->id,
            granteeId: $doctor->id,
            resourceType: 'lab_report',
            resourceId: 1,
            ttlMinutes: 60,
        );

        $response = $this->actingAs($patient)->get(route('patient.consent-center'));
        $response->assertOk();
        $response->assertViewIs('patient.consent-center');
        $response->assertSee($doctor->name);

        // Revoke token
        $revokeResponse = $this->actingAs($patient)->post(route('patient.consent.revoke', $token->id));
        $revokeResponse->assertRedirect(route('patient.consent-center'));
        $revokeResponse->assertSessionHas('status', 'Consent token revoked successfully.');

        $this->assertFalse($consentService->validate($token->id));
    }

    public function test_uploading_lab_report_stores_file_and_auto_issues_24h_consent(): void
    {
        Storage::fake('private');

        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $file = UploadedFile::fake()->create('blood_test.pdf', 500, 'application/pdf');

        $response = $this->actingAs($patient)->post(route('patient.lab-reports.store'), [
            'report_title' => 'Complete Blood Count (CBC)',
            'doctor_id' => $doctor->id,
            'report_file' => $file,
        ]);

        $response->assertRedirect(route('patient.timeline'));
        $response->assertSessionHas('status');

        $labReport = LabReport::where('patient_id', $patient->id)->first();
        $this->assertNotNull($labReport);
        $this->assertEquals('Complete Blood Count (CBC)', $labReport->report_title);
        $this->assertEquals($doctor->id, $labReport->doctor_id);

        Storage::disk('private')->assertExists($labReport->file_path);

        // Verify that a 24-hour consent token was automatically issued to the assigned doctor
        $consentToken = ConsentToken::where('patient_id', $patient->id)
            ->where('grantee_id', $doctor->id)
            ->where('resource_type', 'lab_report')
            ->where('resource_id', $labReport->id)
            ->first();

        $this->assertNotNull($consentToken);
        $this->assertTrue($consentToken->isActive());
        $this->assertNotNull($consentToken->expires_at);
        // Expiration should be roughly 24 hours from now
        $this->assertTrue($consentToken->expires_at->greaterThan(now()->addHours(23)));
    }

    public function test_patient_can_submit_assistance_request(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $response = $this->actingAs($patient)->post(route('patient.assistance-requests.store'), [
            'doctor_id' => $doctor->id,
            'message' => 'I have a question about my medicine dosage and mild side effects.',
        ]);

        $response->assertRedirect(route('patient.dashboard'));
        $response->assertSessionHas('status', 'Assistance request submitted successfully.');

        $this->assertDatabaseHas('assistance_requests', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'open',
            'message' => 'I have a question about my medicine dosage and mild side effects.',
        ]);
    }
}
