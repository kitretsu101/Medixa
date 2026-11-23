<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ConsentToken;
use App\Models\LabReport;
use App\Models\LiveQueue;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_consult_prescribe_and_advance_with_existing_side_effects(): void
    {
        $this->freezeTime();
        $doctor = User::factory()->doctor()->create();
        $first = $this->appointment($doctor, 1, 'Current Patient');
        $next = $this->appointment($doctor, 2, 'Next Patient Name');
        $queue = LiveQueue::create(['doctor_id' => $doctor->id, 'current_running_serial' => 1]);
        $report = LabReport::factory()->create(['patient_id' => $first->patient_id, 'doctor_id' => $doctor->id, 'review_status' => 'pending']);
        $this->actingAs($doctor)->get(route('doctor.dashboard'))->assertSee("Let's Start", false)->assertSee(route('doctor.consultation'), false);
        $page = $this->get(route('doctor.consultation'))->assertOk()->assertSee('Current Patient')->assertDontSee('Next Patient Name')
            ->assertSee('No consented prescriptions')->assertSee('name="diagnosis"', false)
            ->assertSee('action="'.route('doctor.consultation.prescriptions.store', $first).'"', false);
        $this->assertSame(1, substr_count($page->getContent(), '<!DOCTYPE html>'));
        $this->assertDatabaseCount('audit_log', 0);

        $this->post(route('doctor.consultation.prescriptions.store', $first), $this->prescriptionData())
            ->assertRedirect(route('doctor.consultation'))->assertSessionHas('success');
        $prescription = Prescription::where('appointment_id', $first->id)->sole();
        $this->assertSame('Consultation diagnosis', $prescription->diagnosis);
        $this->assertSame('completed', $first->fresh()->status);
        $this->assertNotNull($first->fresh()->consultation_ended_at);
        $this->assertSame('reviewed', $report->fresh()->review_status);
        $this->assertDatabaseHas('tracker_invitations', ['prescription_id' => $prescription->id, 'patient_id' => $first->patient_id]);
        $this->assertDatabaseHas('medication_schedules', ['prescription_id' => $prescription->id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $first->patient_id]);
        $this->assertDatabaseCount('consent_tokens', 0);
        $this->get(route('doctor.consultation'))->assertSee('Current Patient');
        $this->post(route('doctor.consultation.next'))->assertRedirect(route('doctor.consultation'));
        $this->assertSame(2, $queue->fresh()->current_running_serial);
        $this->get(route('doctor.consultation'))->assertSee('Next Patient Name')->assertDontSee('Current Patient');
        $this->assertDatabaseMissing('prescriptions', ['appointment_id' => $next->id]);
    }

    public function test_next_without_prescribing_uses_the_existing_queue_optimizer(): void
    {
        $this->freezeTime();
        $doctor = User::factory()->doctor()->create();
        $this->appointment($doctor, 1, 'First Patient');
        $next = $this->appointment($doctor, 3, 'Checked In Patient');
        $next->update(['status' => 'checked_in', 'checked_in_at' => now()]);
        $queue = LiveQueue::create(['doctor_id' => $doctor->id, 'current_running_serial' => 1]);

        $this->actingAs($doctor)->post(route('doctor.consultation.next'), ['doctor_id' => 999])
            ->assertRedirect(route('doctor.consultation'));
        $this->assertSame(3, $queue->fresh()->current_running_serial);
        $this->assertSame('in_progress', $next->fresh()->status);
        $this->get(route('doctor.consultation'))->assertSee('Checked In Patient');
        $this->assertDatabaseCount('prescriptions', 0);
    }

    public function test_history_reuses_the_same_consent_gating_and_exact_audit_count(): void
    {
        $this->freezeTime();
        $doctor = User::factory()->doctor()->create();
        $appointment = $this->appointment($doctor, 1, 'Consent Patient');
        LiveQueue::create(['doctor_id' => $doctor->id, 'current_running_serial' => 1]);
        $report = LabReport::factory()->create(['doctor_id' => $doctor->id, 'patient_id' => $appointment->patient_id, 'report_title' => 'Visible report']);
        $prescription = Prescription::factory()->create(['appointment_id' => $appointment->id, 'doctor_id' => $doctor->id, 'patient_id' => $appointment->patient_id, 'diagnosis' => 'Visible history diagnosis']);
        foreach (['lab_report' => $report, 'prescription' => $prescription] as $type => $resource) {
            ConsentToken::factory()->create(['patient_id' => $appointment->patient_id, 'grantee_id' => $doctor->id, 'resource_type' => $type, 'resource_id' => $resource->id]);
        }
        $this->actingAs($doctor)->get(route('doctor.patients.history', $appointment->patient))->assertSee('Visible report');
        $before = AuditLog::count();
        $page = $this->get(route('doctor.consultation'))->assertOk()->assertSee('Visible report')->assertSee('Visible history diagnosis');
        $this->assertSame(2, $before);
        $this->assertSame(4, AuditLog::count());
        $this->assertSame(2, AuditLog::where('resource_type', 'lab_report')->where('resource_id', $report->id)->count());
        $this->assertSame(2, AuditLog::where('resource_type', 'prescription')->where('resource_id', $prescription->id)->count());
        if ($directory = getenv('MEDIXA_CONSULTATION_REVIEW_DIRECTORY')) {
            file_put_contents($directory.DIRECTORY_SEPARATOR.'consultation.html', $page->getContent());
        }
    }

    #[TestWith(['expired'])]
    #[TestWith(['revoked'])]
    public function test_history_is_rechecked_on_every_reload_when_consent_is_no_longer_valid(string $state): void
    {
        $this->freezeTime();
        $doctor = User::factory()->doctor()->create();
        $appointment = $this->appointment($doctor, 1, 'Consent Patient');
        LiveQueue::create(['doctor_id' => $doctor->id, 'current_running_serial' => 1]);
        $report = LabReport::factory()->create(['doctor_id' => $doctor->id, 'patient_id' => $appointment->patient_id, 'report_title' => 'Private clinical report']);
        $token = ConsentToken::factory()->create(['patient_id' => $appointment->patient_id, 'grantee_id' => $doctor->id, 'resource_type' => 'lab_report', 'resource_id' => $report->id]);
        $this->actingAs($doctor)->get(route('doctor.consultation'))->assertSee('Private clinical report');
        $token->update($state === 'revoked' ? ['revoked_at' => now()] : ['expires_at' => now()]);
        $this->get(route('doctor.consultation'))->assertDontSee('Private clinical report')->assertSee('No consented lab reports');
        $this->assertDatabaseCount('audit_log', 1);
    }

    public function test_each_reload_and_tab_uses_the_current_doctors_current_queue_and_todays_date(): void
    {
        $this->freezeTime();
        $doctor = User::factory()->doctor()->create();
        $this->appointment($doctor, 1, 'Earlier Patient');
        $this->appointment($doctor, 2, 'Current Queue Patient');
        $old = $this->appointment($doctor, 1, 'Yesterday Patient');
        $old->update(['appointment_date' => today()->subDay()]);
        $other = User::factory()->doctor()->create();
        $this->appointment($other, 1, 'Other Doctors Patient');
        $queue = LiveQueue::create(['doctor_id' => $doctor->id, 'current_running_serial' => 1]);
        $this->actingAs($doctor)->get(route('doctor.consultation'))->assertSee('Earlier Patient')->assertDontSee('Yesterday Patient');
        $queue->update(['current_running_serial' => 2]);
        $url = route('doctor.consultation', ['doctor_id' => $other->id, 'patient' => 999, 'serial_number' => 1]);
        $this->get($url)->assertSee('Current Queue Patient')->assertDontSee('Earlier Patient')->assertDontSee('Other Doctors Patient');
        $this->get($url)->assertSee('Current Queue Patient');
        $this->assertDatabaseCount('audit_log', 0);
    }

    #[TestWith([0])]
    #[TestWith([5])]
    public function test_missing_current_appointment_has_an_empty_state(int $serial): void
    {
        $this->freezeTime();
        $doctor = User::factory()->doctor()->create();
        $this->appointment($doctor, 1, 'Not Current Patient');
        LiveQueue::create(['doctor_id' => $doctor->id, 'current_running_serial' => $serial]);
        $this->actingAs($doctor)->get(route('doctor.consultation'))->assertOk()
            ->assertSee('No patient currently in queue')->assertDontSee('Not Current Patient')->assertDontSee('name="diagnosis"', false);
    }

    public function test_empty_day_initializes_queue_and_next_is_a_no_op(): void
    {
        $this->freezeTime();
        $doctor = User::factory()->doctor()->create();
        $this->actingAs($doctor)->get(route('doctor.consultation'))->assertSee('No patient currently in queue');
        $this->post(route('doctor.consultation.next'))->assertRedirect(route('doctor.consultation'))->assertSessionHas('consultation_status', 'No more patients waiting');
        $this->assertDatabaseHas('live_queues', ['doctor_id' => $doctor->id, 'current_running_serial' => 0]);
        $this->get(route('doctor.consultation'))->assertSee('No more patients waiting')->assertSee('No patient currently in queue');
    }

    public function test_final_patient_stays_visible_when_the_bounded_increment_cannot_advance(): void
    {
        $this->freezeTime();
        $doctor = User::factory()->doctor()->create();
        $this->appointment($doctor, 1, 'Final Patient');
        $queue = LiveQueue::create(['doctor_id' => $doctor->id, 'current_running_serial' => 1]);
        $this->actingAs($doctor)->post(route('doctor.consultation.next'))->assertSessionHas('consultation_status', 'No more patients waiting');
        $this->assertSame(1, $queue->fresh()->current_running_serial);
        $this->get(route('doctor.consultation'))->assertSee('Final Patient')->assertSee('No more patients waiting');
        $this->get(route('doctor.consultation'))->assertSee('Final Patient');
    }

    public function test_invalid_prescription_returns_to_inline_form_without_side_effects(): void
    {
        $doctor = User::factory()->doctor()->create();
        $appointment = $this->appointment($doctor, 1, 'Current Patient');
        LiveQueue::create(['doctor_id' => $doctor->id, 'current_running_serial' => 1]);
        $this->actingAs($doctor)->from(route('doctor.consultation'))->post(route('doctor.consultation.prescriptions.store', $appointment), [])
            ->assertRedirect(route('doctor.consultation'))->assertSessionHasErrors(['diagnosis', 'medicines']);
        $this->assertDatabaseCount('prescriptions', 0);
        $this->assertSame('pending', $appointment->fresh()->status);
    }

    public function test_a_stale_tab_does_not_retarget_its_prescription_to_the_new_current_patient(): void
    {
        $this->freezeTime();
        $doctor = User::factory()->doctor()->create();
        $original = $this->appointment($doctor, 1, 'Original Patient');
        $next = $this->appointment($doctor, 2, 'New Current Patient');
        $queue = LiveQueue::create(['doctor_id' => $doctor->id, 'current_running_serial' => 1]);
        $this->actingAs($doctor)->get(route('doctor.consultation'))
            ->assertSee('action="'.route('doctor.consultation.prescriptions.store', $original).'"', false);
        $queue->update(['current_running_serial' => 2]);

        $this->post(route('doctor.consultation.prescriptions.store', $original), $this->prescriptionData())
            ->assertRedirect(route('doctor.consultation'));

        $this->assertDatabaseHas('prescriptions', ['appointment_id' => $original->id, 'patient_id' => $original->patient_id]);
        $this->assertDatabaseMissing('prescriptions', ['appointment_id' => $next->id]);
        $this->get(route('doctor.consultation'))->assertSee('New Current Patient')->assertDontSee('Original Patient');
    }

    public function test_other_doctor_sees_only_own_queue_and_cannot_save_for_another_doctors_appointment(): void
    {
        $owner = User::factory()->doctor()->create();
        $appointment = $this->appointment($owner, 1, 'Private Patient');
        $queue = LiveQueue::create(['doctor_id' => $owner->id, 'current_running_serial' => 1]);
        $other = User::factory()->doctor()->create();
        $this->actingAs($other)->get(route('doctor.consultation', ['doctor_id' => $owner->id]))
            ->assertSee('No patient currently in queue')->assertDontSee('Private Patient');
        $this->post(route('doctor.consultation.next'), ['doctor_id' => $owner->id])->assertRedirect(route('doctor.consultation'));
        $this->assertSame(1, $queue->fresh()->current_running_serial);
        $this->post(route('doctor.consultation.prescriptions.store', $appointment), $this->prescriptionData())->assertForbidden();
        $this->assertDatabaseCount('prescriptions', 0);
    }

    #[TestWith(['patient'])]
    #[TestWith(['admin'])]
    #[TestWith(['receptionist'])]
    #[TestWith(['lab_assistant'])]
    public function test_other_hospital_roles_are_forbidden_from_all_consultation_actions(string $role): void
    {
        $appointment = Appointment::factory()->create();
        $this->actingAs(User::factory()->create(['role' => $role]));
        $this->get(route('doctor.consultation'))->assertForbidden();
        $this->post(route('doctor.consultation.next'))->assertForbidden();
        $this->post(route('doctor.consultation.prescriptions.store', $appointment), [])->assertForbidden();
    }

    public function test_guests_must_log_in_for_all_consultation_actions(): void
    {
        $appointment = Appointment::factory()->create();
        $this->get(route('doctor.consultation'))->assertRedirect(route('login'));
        $this->post(route('doctor.consultation.next'))->assertRedirect(route('login'));
        $this->post(route('doctor.consultation.prescriptions.store', $appointment), [])->assertRedirect(route('login'));
    }

    private function appointment(User $doctor, int $serial, string $name): Appointment
    {
        return Appointment::factory()->create([
            'doctor_id' => $doctor->id, 'patient_id' => User::factory()->patient()->create(['name' => $name])->id,
            'appointment_date' => today(), 'serial_number' => $serial, 'status' => 'pending',
        ]);
    }

    /** @return array{diagnosis: string, medicines: list<array{name: string, dosage: string, frequency: string, duration: string}>, instructions: string} */
    private function prescriptionData(): array
    {
        return ['diagnosis' => 'Consultation diagnosis', 'medicines' => [['name' => 'Medicine', 'dosage' => '10mg', 'frequency' => '1-0-1', 'duration' => '5 days']], 'instructions' => 'Existing prescription flow'];
    }
}
