<?php

namespace Tests\Feature;

use App\Models\LabReport;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LabReportUploadTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['pdf'])]
    #[TestWith(['png'])]
    #[TestWith(['jpg'])]
    public function test_lab_report_is_written_to_real_private_storage_and_appears_in_history(string $extension): void
    {
        Notification::fake();
        $directory = storage_path('framework/testing/lab-upload-'.Str::uuid());
        Storage::set('private', Storage::build(['driver' => 'local', 'root' => $directory]));
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $assistant = User::factory()->create(['role' => 'lab_assistant']);
        $report = $this->pendingReport($patient, $doctor);
        $contents = $this->reportContents($extension);
        try {
            $this->actingAs($assistant)->post(route('lab.reports.store', $report), [
                'report_file' => UploadedFile::fake()->createWithContent('real-report.'.$extension, $contents),
            ])->assertSessionHasNoErrors()->assertRedirect(route('lab.history'));
            $report->refresh();
            $this->assertSame('uploaded', $report->upload_status);
            $this->assertSame($assistant->id, $report->uploaded_by);
            $this->assertFileExists($directory.DIRECTORY_SEPARATOR.$report->file_path);
            $this->assertSame($contents, Storage::disk('private')->get($report->file_path));
            $this->get(route('lab.history'))->assertOk()->assertSee($report->reference)->assertSee('File uploaded successfully.');
        } finally {
            if ($report->file_path) {
                Storage::disk('private')->delete($report->file_path);
            }
        }
    }

    #[TestWith(['patient', 'png'])]
    #[TestWith(['patient', 'jpg'])]
    #[TestWith(['patient', 'jpeg'])]
    #[TestWith(['patient', 'pdf'])]
    #[TestWith(['lab_assistant', 'png'])]
    #[TestWith(['lab_assistant', 'jpg'])]
    #[TestWith(['lab_assistant', 'jpeg'])]
    #[TestWith(['lab_assistant', 'pdf'])]
    public function test_both_portals_accept_real_report_content_above_two_megabytes(string $role, string $extension): void
    {
        Storage::fake('private');
        Notification::fake();
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $report = $role === 'lab_assistant' ? $this->pendingReport($patient, $doctor) : null;
        $actor = $report ? User::factory()->create(['role' => 'lab_assistant']) : $patient;
        $contents = str_pad($this->reportContents($extension), 3 * 1024 * 1024, ' ');

        $this->actingAs($actor)->post($report ? route('lab.reports.store', $report) : route('patient.lab-reports.store'), [
            'report_title' => 'Upload regression report', 'doctor_id' => $doctor->id,
            'report_file' => UploadedFile::fake()->createWithContent('report.'.$extension, $contents),
        ])->assertSessionHasNoErrors()->assertSessionHas('report_uploaded', true)
            ->assertRedirect($report ? route('lab.history') : route('patient.timeline'));

        $this->get($report ? route('lab.history') : route('patient.timeline'))
            ->assertOk()->assertSee('File uploaded successfully.')->assertSee('role="status"', false);

        $stored = LabReport::sole();
        $this->assertSame('uploaded', $stored->upload_status);
        $this->assertSame($patient->id, $stored->patient_id);
        $this->assertSame($doctor->id, $stored->doctor_id);
        Storage::disk('private')->assertExists($stored->file_path);
        $this->assertSame(strlen($contents), Storage::disk('private')->size($stored->file_path));
        $this->assertDatabaseHas('consent_tokens', ['patient_id' => $patient->id, 'grantee_id' => $doctor->id, 'resource_type' => 'lab_report', 'resource_id' => $stored->id]);
    }

    #[TestWith(['patient'])]
    #[TestWith(['lab_assistant'])]
    public function test_upload_form_has_selected_filename_and_remove_control_without_success_confirmation(string $role): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $report = $role === 'lab_assistant' ? $this->pendingReport($patient, $doctor) : null;
        $actor = $report ? User::factory()->create(['role' => 'lab_assistant']) : $patient;

        $this->actingAs($actor)->get($report ? route('lab.reports.upload', $report) : route('patient.lab-reports.create'))
            ->assertOk()->assertSee('No file selected.')->assertSee('Remove file')
            ->assertSee('data-remove-file', false)->assertDontSee('File uploaded successfully.');
    }

    #[TestWith(['patient'])]
    #[TestWith(['lab_assistant'])]
    public function test_failed_private_storage_does_not_create_or_mark_a_report_uploaded(string $role): void
    {
        Notification::fake();
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $report = $role === 'lab_assistant' ? $this->pendingReport($patient, $doctor) : null;
        $actor = $report ? User::factory()->create(['role' => 'lab_assistant']) : $patient;
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('private')->once()->andReturn($disk);

        $this->actingAs($actor)->post($report ? route('lab.reports.store', $report) : route('patient.lab-reports.store'), [
            'report_title' => 'Storage failure', 'doctor_id' => $doctor->id,
            'report_file' => UploadedFile::fake()->createWithContent('report.pdf', $this->reportContents('pdf')),
        ])->assertSessionHasErrors(['report_file' => 'The report could not be saved. Please try again.']);

        $this->assertDatabaseCount('lab_reports', $report ? 1 : 0);
        if ($report) {
            $this->assertSame('pending_upload', $report->fresh()->upload_status);
            $this->assertNull($report->fresh()->file_path);
        }
        $this->assertDatabaseCount('consent_tokens', 0);
        $this->assertDatabaseCount('audit_log', 0);
        Notification::assertNothingSent();
    }

    #[TestWith(['patient'])]
    #[TestWith(['lab_assistant'])]
    public function test_both_portals_still_reject_reports_over_ten_megabytes(string $role): void
    {
        Storage::fake('private');
        $patient = User::factory()->patient()->create();
        $doctor = User::factory()->doctor()->create();
        $report = $role === 'lab_assistant' ? $this->pendingReport($patient, $doctor) : null;
        $actor = $report ? User::factory()->create(['role' => 'lab_assistant']) : $patient;

        $this->actingAs($actor)->post($report ? route('lab.reports.store', $report) : route('patient.lab-reports.store'), [
            'report_title' => 'Oversized report', 'doctor_id' => $doctor->id,
            'report_file' => UploadedFile::fake()->create('oversized.pdf', 10241, 'application/pdf'),
        ])->assertSessionHasErrors('report_file');
        $this->assertSame([], Storage::disk('private')->allFiles());
        $this->assertDatabaseCount('consent_tokens', 0);
    }

    private function pendingReport(User $patient, User $doctor): LabReport
    {
        return LabReport::factory()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'file_path' => null, 'upload_status' => 'pending_upload', 'uploaded_at' => null]);
    }

    private function reportContents(string $extension): string
    {
        return match ($extension) {
            'pdf' => "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n",
            'png' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jzD0AAAAASUVORK5CYII='),
            default => base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/2wBDAQMEBAUEBQkFBQkUDQsNFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBT/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD9U6KKKAP/2Q=='),
        };
    }
}
