<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ConsentToken;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\LabReport;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_doctor_cannot_access_doctor_portal_routes(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->get(route('doctor.dashboard'));
        $response->assertForbidden();

        $response = $this->actingAs($patient)->get(route('doctor.patients.index'));
        $response->assertForbidden();

        $response = $this->actingAs($patient)->get(route('doctor.pending-reviews'));
        $response->assertForbidden();
    }

    public function test_doctor_dashboard_shows_correct_workload_stats(): void
    {
        $doctor = User::factory()->doctor()->create();
        $dept = Department::firstOrCreate(['name' => 'Cardiology']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => $dept->id,
            'qualification' => 'MBBS, MD',
            'designation' => 'Senior Consultant',
            'patients_seen_count' => 5,
        ]);

        $patient1 = User::factory()->patient()->create();
        $patient2 = User::factory()->patient()->create();

        // 2 pending appointments
        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient1->id,
            'status' => 'pending',
        ]);
        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient2->id,
            'status' => 'pending',
        ]);

        // 1 pending lab report
        LabReport::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient1->id,
            'review_status' => 'pending',
        ]);

        // 1 reviewed lab report (should not count as pending)
        LabReport::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient2->id,
            'review_status' => 'reviewed',
        ]);

        $response = $this->actingAs($doctor)->get(route('doctor.dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalPatientsSeen', 5);
        $response->assertViewHas('pendingAppointments', 2);
        $response->assertViewHas('pendingReportReviews', 1);
    }

    public function test_doctor_can_view_linked_patients_list(): void
    {
        $doctor = User::factory()->doctor()->create();
        $otherDoctor = User::factory()->doctor()->create();

        $linkedPatient = User::factory()->patient()->create(['name' => 'Linked Patient Alice']);
        $unlinkedPatient = User::factory()->patient()->create(['name' => 'Unlinked Patient Bob']);

        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $linkedPatient->id,
        ]);

        Appointment::factory()->create([
            'doctor_id' => $otherDoctor->id,
            'patient_id' => $unlinkedPatient->id,
        ]);

        $response = $this->actingAs($doctor)->get(route('doctor.patients.index'));

        $response->assertOk();
        $response->assertSee('Linked Patient Alice');
        $response->assertDontSee('Unlinked Patient Bob');
    }

    public function test_patient_history_shows_only_consented_resources_and_logs_audit_entries(): void
    {
        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->patient()->create();

        // 2 Lab Reports
        $consentedReport = LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'report_title' => 'Consented CBC Test',
        ]);
        $unconsentedReport = LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'report_title' => 'Secret MRI Scan',
        ]);

        // 2 Prescriptions
        $appt1 = Appointment::factory()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id]);
        $consentedRx = Prescription::factory()->create([
            'appointment_id' => $appt1->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Consented Diagnosis Flu',
        ]);

        $appt2 = Appointment::factory()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id]);
        $unconsentedRx = Prescription::factory()->create([
            'appointment_id' => $appt2->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Secret Psychiatric Evaluation',
        ]);

        // Issue valid consent tokens ONLY for $consentedReport and $consentedRx
        $tokenReport = ConsentToken::factory()->create([
            'patient_id' => $patient->id,
            'grantee_id' => $doctor->id,
            'resource_type' => 'lab_report',
            'resource_id' => $consentedReport->id,
            'expires_at' => now()->addHour(),
            'revoked_at' => null,
        ]);

        $tokenRx = ConsentToken::factory()->create([
            'patient_id' => $patient->id,
            'grantee_id' => $doctor->id,
            'resource_type' => 'prescription',
            'resource_id' => $consentedRx->id,
            'expires_at' => now()->addHour(),
            'revoked_at' => null,
        ]);

        // Expired token for unconsentedRx to test expiry shielding
        ConsentToken::factory()->expired()->create([
            'patient_id' => $patient->id,
            'grantee_id' => $doctor->id,
            'resource_type' => 'prescription',
            'resource_id' => $unconsentedRx->id,
        ]);

        $this->assertDatabaseCount('audit_log', 0);

        $response = $this->actingAs($doctor)->get(route('doctor.patients.history', $patient));

        $response->assertOk();
        // Consented records visible
        $response->assertSee('Consented CBC Test');
        $response->assertSee('Consented Diagnosis Flu');
        $response->assertSee('Consent-Scoped Access');

        // Unconsented records shielded
        $response->assertDontSee('Secret MRI Scan');
        $response->assertDontSee('Secret Psychiatric Evaluation');

        // Verify audit log has 2 entries recorded for the 2 accessed resources
        $this->assertDatabaseCount('audit_log', 2);
        $this->assertDatabaseHas('audit_log', [
            'actor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'resource_type' => 'lab_report',
            'resource_id' => $consentedReport->id,
            'consent_token_id' => $tokenReport->id,
        ]);
        $this->assertDatabaseHas('audit_log', [
            'actor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'resource_type' => 'prescription',
            'resource_id' => $consentedRx->id,
            'consent_token_id' => $tokenRx->id,
        ]);
    }

    public function test_pending_reviews_inbox_displays_pending_reports_with_token_data(): void
    {
        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->patient()->create();

        $pendingReport = LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'report_title' => 'Urgent Blood Sugar Panel',
            'review_status' => 'pending',
        ]);

        $token = ConsentToken::factory()->create([
            'patient_id' => $patient->id,
            'grantee_id' => $doctor->id,
            'resource_type' => 'lab_report',
            'resource_id' => $pendingReport->id,
            'expires_at' => now()->addMinutes(45),
            'revoked_at' => null,
        ]);

        $response = $this->actingAs($doctor)->get(route('doctor.pending-reviews'));

        $response->assertOk();
        $response->assertSee('Urgent Blood Sugar Panel');
        $response->assertSee('View Report');
    }

    public function test_doctor_can_create_prescription_completing_appointment_and_marking_report_reviewed(): void
    {
        $doctor = User::factory()->doctor()->create();
        $dept = Department::firstOrCreate(['name' => 'General Medicine']);
        $doctorProfile = DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => $dept->id,
            'qualification' => 'MBBS',
            'designation' => 'Resident Physician',
            'patients_seen_count' => 0,
        ]);

        $patient = User::factory()->patient()->create();
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'pending',
        ]);

        $pendingReport = LabReport::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'review_status' => 'pending',
        ]);

        $postData = [
            'diagnosis' => 'Acute bacterial pharyngitis',
            'medicines' => [
                [
                    'name' => 'Amoxicillin',
                    'dosage' => '500mg',
                    'frequency' => '1-0-1',
                    'duration' => '7 days',
                ],
            ],
            'instructions' => 'Complete the antibiotic course and gargle with warm salt water.',
        ];

        $response = $this->actingAs($doctor)->post(
            route('doctor.prescriptions.store', $appointment),
            $postData
        );

        $response->assertRedirect(route('doctor.dashboard'));
        $response->assertSessionHas('success');

        // Verify prescription created
        $this->assertDatabaseHas('prescriptions', [
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Acute bacterial pharyngitis',
        ]);

        // Verify appointment completed
        $this->assertEquals('completed', $appointment->fresh()->status);

        // Verify pending lab report marked reviewed
        $this->assertEquals('reviewed', $pendingReport->fresh()->review_status);

        // Verify doctor patients seen count incremented
        $this->assertEquals(1, $doctorProfile->fresh()->patients_seen_count);
    }

    public function test_doctor_cannot_prescribe_for_another_doctors_appointment(): void
    {
        $doctor = User::factory()->doctor()->create();
        $otherDoctor = User::factory()->doctor()->create();
        $patient = User::factory()->patient()->create();

        $appointment = Appointment::factory()->create([
            'doctor_id' => $otherDoctor->id,
            'patient_id' => $patient->id,
            'status' => 'pending',
        ]);

        // Attempt to view builder
        $response = $this->actingAs($doctor)->get(route('doctor.prescriptions.create', $appointment));
        $response->assertForbidden();

        // Attempt to post prescription
        $response = $this->actingAs($doctor)->post(route('doctor.prescriptions.store', $appointment), [
            'diagnosis' => 'Unauthorized attempt',
            'medicines' => [['name' => 'Drug', 'dosage' => '10mg']],
        ]);
        $response->assertForbidden();
    }
}
