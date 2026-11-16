<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ConsentToken;
use App\Models\LabReport;
use App\Models\Prescription;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EmergencyAccessService;
use App\Support\DoctorName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EmergencyAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_emergency_post_returns_the_file_without_granting_normal_or_future_access(): void
    {
        Storage::fake('private');
        $report = LabReport::factory()->create(['file_path' => 'reports/emergency.pdf']);
        Storage::disk('private')->put($report->file_path, 'private-report-bytes');
        $this->actingAs($report->doctor);

        $this->get(route('doctor.lab-reports.show', $report))->assertForbidden();
        $response = $this->post($this->emergencyUrl($report), ['reason' => 'Patient is unconscious and requires immediate care.']);

        $response->assertOk()->assertStreamedContent('private-report-bytes');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        $this->assertDatabaseCount('consent_tokens', 0);
        $this->assertDatabaseCount('audit_log', 1);

        $this->get(route('doctor.lab-reports.show', $report))->assertForbidden();
        $this->get(route('doctor.lab-reports.open', $report))->assertForbidden();
        $this->get($report->getTemporaryDownloadUrl())->assertForbidden();
        $this->get($this->emergencyUrl($report))->assertMethodNotAllowed();
        $this->assertDatabaseCount('audit_log', 1);
    }

    public function test_service_logs_the_exact_reason_and_notifies_only_the_correct_patient(): void
    {
        $this->freezeSecond();
        $report = LabReport::factory()->create();
        $otherPatient = User::factory()->patient()->create();
        $reason = 'Immediate treatment needed for an unconscious patient.';

        $success = app(EmergencyAccessService::class)->grantEmergencyAccess(
            $report->doctor_id, $report->patient_id, 'lab_report', $report->id, $reason, '127.0.0.1',
        );

        $this->assertTrue($success);
        $this->assertDatabaseCount('audit_log', 1);
        $this->assertDatabaseHas('audit_log', [
            'actor_id' => $report->doctor_id,
            'patient_id' => $report->patient_id,
            'resource_type' => 'lab_report',
            'resource_id' => $report->id,
            'consent_token_id' => null,
            'ip_address' => '127.0.0.1',
            'is_emergency_override' => true,
            'override_reason' => $reason,
        ]);
        $this->assertTrue(AuditLog::query()->sole()->is_emergency_override);
        $this->assertDatabaseCount('consent_tokens', 0);
        $this->assertDatabaseCount('notifications', 1);
        $notification = $report->patient->notifications()->sole();
        $this->assertSame(
            DoctorName::format($report->doctor->name).' used emergency access to view your lab report on '
                .now()->format('Y-m-d H:i:s').' — reason: '.$reason,
            $notification->data['body'],
        );
        $this->assertSame('warning', $notification->data['level']);
        $this->assertSame(0, $otherPatient->notifications()->count());
        $this->assertSame(0, $report->doctor->notifications()->count());
    }

    #[TestWith([''])]
    #[TestWith(['short'])]
    #[TestWith(['          '])]
    #[TestWith(['   short   '])]
    #[TestWith(['123456789'])]
    #[TestWith(["\u{00A0}\u{00A0}\u{00A0}\u{00A0}\u{00A0}\u{00A0}\u{00A0}\u{00A0}\u{00A0}\u{00A0}"])]
    public function test_service_rejects_empty_short_and_whitespace_padded_reasons_without_side_effects(string $reason): void
    {
        $report = LabReport::factory()->create();

        try {
            app(EmergencyAccessService::class)->grantEmergencyAccess(
                $report->doctor_id, $report->patient_id, 'lab_report', $report->id, $reason,
            );
            $this->fail('Invalid emergency reasons must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }

        $this->assertDatabaseCount('audit_log', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('consent_tokens', 0);
    }

    public function test_service_accepts_a_reason_of_exactly_ten_characters(): void
    {
        $report = LabReport::factory()->create();

        $this->assertTrue(app(EmergencyAccessService::class)->grantEmergencyAccess(
            $report->doctor_id, $report->patient_id, 'lab_report', $report->id, '1234567890',
        ));

        $this->assertDatabaseHas('audit_log', ['override_reason' => '1234567890', 'is_emergency_override' => true]);
        $this->assertDatabaseCount('notifications', 1);
    }

    #[TestWith([null])]
    #[TestWith([123])]
    #[TestWith([['invalid']])]
    public function test_emergency_post_rejects_missing_or_non_string_reasons_with_422(mixed $reason): void
    {
        $report = LabReport::factory()->create();

        $this->actingAs($report->doctor)->postJson($this->emergencyUrl($report), ['reason' => $reason])
            ->assertUnprocessable()->assertJsonValidationErrors('reason');

        $this->assertDatabaseCount('audit_log', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_guest_is_redirected_to_login_without_emergency_side_effects(): void
    {
        $report = LabReport::factory()->create();

        $this->post($this->emergencyUrl($report), ['reason' => 'Immediate treatment is necessary.'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('audit_log', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    #[TestWith(['patient'])]
    #[TestWith(['admin'])]
    #[TestWith(['lab_assistant'])]
    #[TestWith(['receptionist'])]
    public function test_non_doctor_roles_receive_403_on_the_emergency_route(string $role): void
    {
        $report = LabReport::factory()->create();
        $actor = User::factory()->create(['role' => $role]);

        $this->actingAs($actor)->post($this->emergencyUrl($report), ['reason' => 'Immediate treatment is necessary.'])
            ->assertForbidden();

        $this->assertDatabaseCount('audit_log', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_service_also_rejects_a_non_doctor_actor(): void
    {
        $report = LabReport::factory()->create();

        try {
            app(EmergencyAccessService::class)->grantEmergencyAccess(
                $report->patient_id, $report->patient_id, 'lab_report', $report->id, 'Immediate treatment is necessary.',
            );
            $this->fail('A non-doctor must not be allowed emergency access.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertDatabaseCount('audit_log', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_resource_belonging_to_a_different_patient_returns_404_without_side_effects(): void
    {
        $report = LabReport::factory()->create();
        $otherPatient = User::factory()->patient()->create();

        $this->actingAs($report->doctor)->post(route('doctor.patients.emergency-access', [
            'patient' => $otherPatient,
            'resourceType' => 'lab_report',
            'resourceId' => $report->id,
        ]), ['reason' => 'Immediate treatment is necessary.'])->assertNotFound();

        $this->assertDatabaseCount('audit_log', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    #[TestWith(['unknown', 1])]
    #[TestWith(['lab_report', 999999])]
    public function test_unsupported_or_missing_resources_return_404(string $resourceType, int $resourceId): void
    {
        $report = LabReport::factory()->create();

        $this->actingAs($report->doctor)->post(route('doctor.patients.emergency-access', [
            'patient' => $report->patient_id,
            'resourceType' => $resourceType,
            'resourceId' => $resourceId,
        ]), ['reason' => 'Immediate treatment is necessary.'])->assertNotFound();

        $this->assertDatabaseCount('audit_log', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_a_non_patient_target_returns_404_without_side_effects(): void
    {
        $report = LabReport::factory()->create();

        $this->actingAs($report->doctor)->post(route('doctor.patients.emergency-access', [
            'patient' => $report->doctor_id,
            'resourceType' => 'lab_report',
            'resourceId' => $report->id,
        ]), ['reason' => 'Immediate treatment is necessary.'])->assertNotFound();

        $this->assertDatabaseCount('audit_log', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_missing_report_file_returns_404_without_logging_a_success_or_notifying(): void
    {
        Storage::fake('private');
        $report = LabReport::factory()->create();

        $this->actingAs($report->doctor)->post($this->emergencyUrl($report), ['reason' => 'Immediate treatment is necessary.'])
            ->assertNotFound();

        $this->assertDatabaseCount('audit_log', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_each_explicit_emergency_post_creates_its_own_audit_and_patient_notification(): void
    {
        Storage::fake('private');
        $report = LabReport::factory()->create();
        Storage::disk('private')->put($report->file_path, 'private-report-bytes');
        $this->actingAs($report->doctor);

        $this->post($this->emergencyUrl($report), ['reason' => 'First emergency examination.'])->assertOk();
        $this->post($this->emergencyUrl($report), ['reason' => 'Second emergency examination.'])->assertOk();

        $this->assertDatabaseCount('audit_log', 2);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseCount('consent_tokens', 0);
        $this->assertDatabaseHas('audit_log', ['override_reason' => 'First emergency examination.']);
        $this->assertDatabaseHas('audit_log', ['override_reason' => 'Second emergency examination.']);
    }

    public function test_prescription_is_shown_only_in_the_emergency_post_and_its_content_is_escaped(): void
    {
        $prescription = Prescription::factory()->create([
            'diagnosis' => '<script>alert("private")</script>',
            'instructions' => 'Emergency instructions for this visit.',
        ]);
        $doctor = $prescription->doctor;

        $response = $this->actingAs($doctor)->post(route('doctor.patients.emergency-access', [
            'patient' => $prescription->patient_id,
            'resourceType' => 'prescription',
            'resourceId' => $prescription->id,
        ]), ['reason' => 'Immediate treatment is necessary.']);

        $response->assertOk()->assertViewIs('doctor.emergency-prescription')
            ->assertSee($prescription->diagnosis)->assertDontSee($prescription->diagnosis, false)
            ->assertSee('Emergency instructions for this visit.');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('doctor.patients.history', $prescription->patient_id))
            ->assertOk()->assertDontSee($prescription->diagnosis)->assertSee('No consented prescriptions');
        $this->assertDatabaseHas('audit_log', ['resource_type' => 'prescription', 'is_emergency_override' => true]);
        $this->assertDatabaseCount('audit_log', 1);
        $this->assertDatabaseCount('consent_tokens', 0);
    }

    public function test_history_retains_no_access_states_and_adds_forms_without_disclosing_hidden_content(): void
    {
        $report = LabReport::factory()->create(['report_title' => 'Hidden laboratory title']);
        $prescription = Prescription::factory()->create([
            'patient_id' => $report->patient_id,
            'doctor_id' => $report->doctor_id,
            'diagnosis' => 'Hidden clinical diagnosis',
        ]);

        $this->actingAs($report->doctor)->get(route('doctor.patients.history', $report->patient_id))
            ->assertOk()->assertSee('No consented lab reports')->assertSee('No consented prescriptions')
            ->assertSee('Request Emergency Access')->assertSee($this->emergencyUrl($report), false)
            ->assertDontSee('Hidden laboratory title')->assertDontSee('Hidden clinical diagnosis')
            ->assertViewHas('emergencyPrescriptionIds', fn ($ids): bool => $ids->all() === [$prescription->id]);

        $this->assertDatabaseCount('audit_log', 0);
    }

    public function test_history_does_not_offer_emergency_access_for_a_consented_resource(): void
    {
        $report = LabReport::factory()->create();
        ConsentToken::factory()->create([
            'patient_id' => $report->patient_id,
            'grantee_id' => $report->doctor_id,
            'resource_type' => 'lab_report',
            'resource_id' => $report->id,
        ]);

        $this->actingAs($report->doctor)->get(route('doctor.patients.history', $report->patient_id))
            ->assertOk()->assertDontSee($this->emergencyUrl($report), false);

        $this->assertDatabaseCount('audit_log', 1);
        $this->assertFalse(AuditLog::query()->sole()->is_emergency_override);
    }

    public function test_admin_badge_reports_emergency_counts_without_exposing_patient_or_reason(): void
    {
        $report = LabReport::factory()->create();
        $admin = User::factory()->admin()->create();
        app(AuditService::class)->log($report->doctor_id, $report->patient_id, 'lab_report', $report->id);
        $this->actingAs($admin)->get(route('admin.audit-oversight'))->assertOk()->assertDontSee('EMERGENCY OVERRIDE');
        app(EmergencyAccessService::class)->grantEmergencyAccess(
            $report->doctor_id, $report->patient_id, 'lab_report', $report->id, 'Confidential emergency reason.',
        );

        $this->get(route('admin.audit-oversight'))->assertOk()->assertSee('EMERGENCY OVERRIDE: 1')
            ->assertDontSee('Confidential emergency reason.')->assertDontSee($report->patient->name)
            ->assertViewHas('weeklyAccessByDoctor', fn ($rows): bool => (int) $rows->sole()->access_count === 2);
    }

    private function emergencyUrl(LabReport $report): string
    {
        return route('doctor.patients.emergency-access', [
            'patient' => $report->patient_id,
            'resourceType' => 'lab_report',
            'resourceId' => $report->id,
        ]);
    }
}
