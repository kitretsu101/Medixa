<?php

namespace Tests\Feature;

use App\Models\ConsentToken;
use App\Models\LabReport;
use App\Models\User;
use App\Services\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LabReportAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_download_own_report_and_another_patient_cannot(): void
    {
        Storage::fake('private');

        $owner = User::factory()->patient()->create();
        $other = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();

        $report = LabReport::factory()->create([
            'patient_id' => $owner->id,
            'doctor_id' => $doctor->id,
            'file_path' => 'reports/cbc.pdf',
        ]);
        Storage::disk('private')->put($report->file_path, 'report-bytes');

        $url = URL::signedRoute('lab-reports.download', ['labReport' => $report->id], now()->addMinutes(2));

        $this->actingAs($owner)->get($url)->assertOk();
        $this->actingAs($other)->get($url)->assertForbidden();
    }

    public function test_doctor_download_requires_a_valid_consent_token(): void
    {
        Storage::fake('private');

        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $otherDoctor = User::factory()->doctor()->create();

        $report = LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'file_path' => 'reports/cbc.pdf',
        ]);
        Storage::disk('private')->put($report->file_path, 'report-bytes');

        $url = URL::signedRoute('lab-reports.download', ['labReport' => $report->id], now()->addMinutes(2));

        $this->actingAs($doctor)->get($url)->assertForbidden();

        ConsentToken::factory()->create([
            'patient_id' => $patient->id,
            'grantee_id' => $doctor->id,
            'resource_type' => 'lab_report',
            'resource_id' => $report->id,
            'expires_at' => now()->addHour(),
            'revoked_at' => null,
        ]);

        $this->actingAs($doctor)->get($url)->assertOk();
        $this->actingAs($otherDoctor)->get($url)->assertForbidden();
        $this->assertDatabaseHas('audit_log', [
            'actor_id' => $doctor->id,
            'resource_id' => $report->id,
            'resource_type' => 'lab_report',
        ]);
    }

    public function test_patient_cannot_revoke_another_patients_consent_token(): void
    {
        $owner = User::factory()->patient()->create();
        $intruder = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $service = app(ConsentService::class);

        $token = $service->issue(
            patientId: $owner->id,
            granteeId: $doctor->id,
            resourceType: 'lab_report',
            resourceId: 1,
            ttlMinutes: 60,
        );

        $this->actingAs($intruder)
            ->post(route('patient.consent.revoke', $token->id))
            ->assertForbidden();

        $this->assertTrue($service->validate($token->id));
    }

    public function test_doctor_cannot_open_an_unlinked_patient_history(): void
    {
        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->patient()->create(['name' => 'Hidden Patient']);

        $this->actingAs($doctor)
            ->get(route('doctor.patients.history', $patient))
            ->assertNotFound();
    }
}
