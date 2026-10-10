<?php

namespace Tests\Feature;

use App\Models\LabReport;
use App\Models\User;
use App\Notifications\MedixaAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LabAssistantPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_assistant_login_opens_only_the_lab_dashboard(): void
    {
        $assistant = User::factory()->create([
            'role' => 'lab_assistant',
        ]);

        $this->post('/login', [
            'email' => $assistant->email,
            'password' => 'password',
        ])->assertRedirect(route('lab.dashboard'));

        $this->actingAs($assistant)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($assistant)->get(route('doctor.dashboard'))->assertForbidden();
        $this->actingAs($assistant)->get(route('doctor.patients.index'))->assertForbidden();
        $this->actingAs($assistant)->get(route('patient.dashboard'))->assertForbidden();
        $this->actingAs($assistant)->get(route('patient.profile'))->assertForbidden();
    }

    public function test_valid_reference_shows_only_the_report_target(): void
    {
        $assistant = User::factory()->create(['role' => 'lab_assistant']);
        $patient = User::factory()->patient()->create([
            'name' => 'Secret Patient Name',
            'email' => 'secret.patient@example.test',
        ]);
        $doctor = User::factory()->doctor()->create(['name' => 'Hidden Doctor']);
        $report = $this->pendingReport($patient, $doctor, 'Complete Blood Count');

        $response = $this->actingAs($assistant)->get(route('lab.reports.upload', $report));

        $response->assertOk();
        $response->assertSee($report->reference);
        $response->assertSee($report->patientReference());
        $response->assertSee('Complete Blood Count');
        $response->assertDontSee('Secret Patient Name');
        $response->assertDontSee('secret.patient@example.test');
        $response->assertDontSee('Hidden Doctor');
    }

    public function test_unknown_reference_does_not_reveal_a_patient(): void
    {
        $assistant = User::factory()->create(['role' => 'lab_assistant']);
        $patient = User::factory()->patient()->create([
            'name' => 'Secret Patient Name',
            'email' => 'secret.patient@example.test',
        ]);

        $response = $this->actingAs($assistant)->get(route('lab.reports.index', [
            'reference' => 'LAB-1999-99999',
        ]));

        $response->assertOk();
        $response->assertSee('No report matches that reference.');
        $response->assertDontSee('Secret Patient Name');
        $response->assertDontSee('secret.patient@example.test');
    }

    public function test_pdf_upload_notifies_patient_and_doctor_without_replacing_a_finished_report(): void
    {
        Storage::fake('private');
        Notification::fake();

        $assistant = User::factory()->create(['role' => 'lab_assistant']);
        $patient = User::factory()->patient()->create(['name' => 'Secret Patient Name']);
        $doctor = User::factory()->doctor()->create();
        $report = $this->pendingReport($patient, $doctor, 'Complete Blood Count');

        $upload = $this->actingAs($assistant)->post(route('lab.reports.store', $report), [
            'report_file' => UploadedFile::fake()->create('cbc.pdf', 120, 'application/pdf'),
        ]);

        $upload->assertRedirect(route('lab.history'));
        $upload->assertSessionHas('status');

        $report->refresh();
        $this->assertSame('uploaded', $report->upload_status);
        $this->assertSame($assistant->id, $report->uploaded_by);
        $this->assertNotNull($report->file_path);
        Storage::disk('private')->assertExists($report->file_path);
        $this->assertDatabaseHas('audit_log', [
            'actor_id' => $assistant->id,
            'resource_type' => 'lab_report',
            'resource_id' => $report->id,
        ]);

        Notification::assertSentTo($patient, MedixaAlert::class);
        Notification::assertSentTo($doctor, MedixaAlert::class);

        $this->actingAs($patient)->get(route('patient.timeline'))
            ->assertOk()
            ->assertSee('Complete Blood Count');

        $url = URL::signedRoute('lab-reports.download', ['labReport' => $report->id], now()->addMinutes(2));
        $this->actingAs($doctor)->get($url)->assertOk();
        $this->actingAs($assistant)->get($url)->assertForbidden();

        $again = $this->actingAs($assistant)->post(route('lab.reports.store', $report), [
            'report_file' => UploadedFile::fake()->create('cbc-again.pdf', 120, 'application/pdf'),
        ]);

        $again->assertRedirect(route('lab.reports.upload', $report));
        $again->assertSessionHasErrors('report_file');
        $this->actingAs($assistant)->get(route('lab.reports.upload', $report))
            ->assertSee('This report has already been uploaded.')
            ->assertDontSee('Secret Patient Name');
    }

    public function test_executable_upload_is_rejected(): void
    {
        Storage::fake('private');

        $assistant = User::factory()->create(['role' => 'lab_assistant']);
        $report = $this->pendingReport(
            User::factory()->patient()->create(),
            User::factory()->doctor()->create(),
            'Complete Blood Count',
        );

        $this->actingAs($assistant)->post(route('lab.reports.store', $report), [
            'report_file' => UploadedFile::fake()->create('malware.exe', 20, 'application/octet-stream'),
        ])->assertSessionHasErrors('report_file');

        $this->assertSame('pending_upload', $report->fresh()->upload_status);
    }

    private function pendingReport(User $patient, User $doctor, string $title): LabReport
    {
        return LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'report_title' => $title,
            'file_path' => null,
            'upload_status' => 'pending_upload',
            'uploaded_at' => null,
            'review_status' => 'pending',
        ]);
    }
}
