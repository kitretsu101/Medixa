<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\LabReport;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_next_serves_a_checked_in_emergency_ahead_of_an_earlier_normal_serial(): void
    {
        $doctor = $this->doctor();
        $normal = User::factory()->patient()->create();
        $emergency = User::factory()->patient()->create();

        Appointment::factory()->create([
            'patient_id' => $normal->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->toDateString(),
            'serial_number' => 1,
            'status' => 'checked_in',
            'priority_type' => 'normal',
            'checked_in_at' => now()->subMinutes(5),
        ]);
        Appointment::factory()->create([
            'patient_id' => $emergency->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->toDateString(),
            'serial_number' => 4,
            'status' => 'checked_in',
            'priority_type' => 'emergency',
            'checked_in_at' => now(),
        ]);

        $this->actingAs($doctor)
            ->postJson(route('doctor.queue.increment'))
            ->assertOk()
            ->assertJson(['current_serial' => 4]);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $emergency->id,
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseHas('queue_events', [
            'doctor_id' => $doctor->id,
            'event' => 'patient_called',
        ]);
    }

    public function test_patient_can_check_in_and_sees_a_calculated_wait(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = $this->doctor();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->toDateString(),
            'serial_number' => 3,
            'status' => 'pending',
            'priority_type' => 'normal',
        ]);

        $this->actingAs($patient)
            ->post(route('patient.appointments.check-in', $appointment))
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'checked_in',
        ]);

        $this->actingAs($patient)
            ->getJson(route('patient.queue.status'))
            ->assertOk()
            ->assertJsonPath('checked_in', true)
            ->assertJsonPath('priority_label', 'Normal');
    }

    public function test_patient_cannot_check_in_someone_elses_appointment(): void
    {
        $owner = User::factory()->patient()->create();
        $other = User::factory()->patient()->create();
        $doctor = $this->doctor();
        $appointment = Appointment::factory()->create([
            'patient_id' => $owner->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $this->actingAs($other)
            ->post(route('patient.appointments.check-in', $appointment))
            ->assertForbidden();
    }

    public function test_routine_booking_stops_before_the_emergency_buffer_is_used(): void
    {
        $doctor = $this->doctor();
        $date = now()->addDay()->toDateString();

        foreach (range(1, AppointmentService::DAILY_CAP - AppointmentService::EMERGENCY_BUFFER) as $serial) {
            Appointment::factory()->create([
                'doctor_id' => $doctor->id,
                'appointment_date' => $date,
                'serial_number' => $serial,
                'status' => 'pending',
                'priority_type' => 'normal',
            ]);
        }

        $patient = User::factory()->patient()->create();

        $this->actingAs($patient)
            ->from(route('patient.appointments.create'))
            ->post(route('patient.appointments.store'), [
                'doctor_id' => $doctor->id,
                'appointment_date' => $date,
                'priority_type' => 'normal',
            ])
            ->assertSessionHasErrors('appointment_date');
    }

    public function test_patient_cannot_book_an_emergency_priority(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = $this->doctor();

        $this->actingAs($patient)
            ->from(route('patient.appointments.create'))
            ->post(route('patient.appointments.store'), [
                'doctor_id' => $doctor->id,
                'appointment_date' => now()->addDay()->toDateString(),
                'priority_type' => 'emergency',
            ])
            ->assertSessionHasErrors('priority_type');
    }

    public function test_recorded_delay_is_shown_to_the_waiting_patient(): void
    {
        $doctor = $this->doctor();
        $patient = User::factory()->patient()->create();
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->toDateString(),
            'serial_number' => 2,
            'status' => 'checked_in',
            'priority_type' => 'normal',
            'checked_in_at' => now(),
        ]);

        $this->actingAs($doctor)
            ->postJson(route('doctor.queue.delay'), ['minutes' => 20])
            ->assertOk();

        $this->actingAs($patient)
            ->getJson(route('patient.queue.status'))
            ->assertOk()
            ->assertJsonPath('delay_minutes', 20)
            ->assertJsonPath('delay_message', 'Doctor is currently delayed by approximately 20 minutes.');
    }

    public function test_receptionist_can_open_the_desk_and_not_the_doctor_chart(): void
    {
        $receptionist = User::factory()->receptionist()->create();

        $this->actingAs($receptionist)
            ->get(route('receptionist.dashboard'))
            ->assertOk()
            ->assertSee('Medical records stay with the doctor');

        $this->actingAs($receptionist)
            ->get(route('doctor.patients.index'))
            ->assertForbidden();

        $this->actingAs($receptionist)
            ->get(route('admin.analytics'))
            ->assertForbidden();
    }

    public function test_doctor_records_a_lab_outcome_and_another_doctor_cannot(): void
    {
        $doctor = $this->doctor();
        $other = $this->doctor();
        $patient = User::factory()->patient()->create();
        $report = LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'upload_status' => 'uploaded',
            'review_status' => 'pending',
            'file_path' => 'lab-reports/sample.pdf',
        ]);

        $this->actingAs($other)
            ->post(route('doctor.lab-reports.review', $report), [
                'clinical_outcome' => 'urgent_clinical_review',
            ])
            ->assertForbidden();

        $this->actingAs($doctor)
            ->post(route('doctor.lab-reports.review', $report), [
                'clinical_outcome' => 'physical_consultation',
                'review_note' => 'Please attend clinic.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('lab_reports', [
            'id' => $report->id,
            'review_status' => 'reviewed',
            'clinical_outcome' => 'physical_consultation',
        ]);
    }

    public function test_admin_analytics_page_labels_the_simulation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('Baseline / simulated comparison');
    }

    private function doctor(): User
    {
        $doctor = User::factory()->doctor()->create();
        $department = Department::query()->create(['name' => 'Medicine '.fake()->unique()->numerify('###')]);
        DoctorProfile::query()->create([
            'user_id' => $doctor->id,
            'department_id' => $department->id,
            'qualification' => 'MBBS',
            'designation' => 'Consultant',
        ]);

        return $doctor;
    }
}
