<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\LiveQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_receives_a_serial_and_cannot_book_the_same_slot_twice(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = $this->doctor();
        $date = now()->addDay()->toDateString();

        $response = $this->actingAs($patient)->post(route('patient.appointments.store'), [
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
        ]);

        $response->assertRedirect(route('patient.appointments.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'serial_number' => 1,
            'status' => 'pending',
        ]);

        $duplicate = $this->actingAs($patient)->from(route('patient.appointments.create'))->post(route('patient.appointments.store'), [
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
        ]);

        $duplicate->assertRedirect(route('patient.appointments.create'));
        $duplicate->assertSessionHasErrors('appointment_date');
        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_queue_status_endpoint_returns_the_patients_own_serial(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = $this->doctor();

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->toDateString(),
            'serial_number' => 8,
            'status' => 'pending',
        ]);

        LiveQueue::create([
            'doctor_id' => $doctor->id,
            'current_running_serial' => 5,
            'doctor_present_status' => true,
        ]);

        $this->actingAs($patient)
            ->getJson(route('patient.queue.status'))
            ->assertOk()
            ->assertJson([
                'active' => true,
                'current_serial' => 5,
                'doctor_present' => true,
                'your_serial' => 8,
            ]);
    }

    public function test_patient_dashboard_shows_the_doctors_current_serial(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = $this->doctor();

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->toDateString(),
            'serial_number' => 8,
            'status' => 'pending',
        ]);

        LiveQueue::create([
            'doctor_id' => $doctor->id,
            'current_running_serial' => 5,
            'doctor_present_status' => true,
        ]);

        $this->actingAs($patient)
            ->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('Now serving')
            ->assertSee('#5')
            ->assertSee('#8');
    }

    private function doctor(): User
    {
        $doctor = User::factory()->doctor()->create();
        $department = Department::query()->create(['name' => 'Cardiology '.fake()->unique()->word()]);
        DoctorProfile::query()->create([
            'user_id' => $doctor->id,
            'department_id' => $department->id,
            'qualification' => 'MBBS',
            'designation' => 'Consultant',
        ]);

        return $doctor;
    }
}
