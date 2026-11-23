<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\Hospital;
use App\Models\User;
use Database\Seeders\MedixaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class HospitalAppointmentApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_request_approval_doctor_visibility_and_check_in_work_end_to_end(): void
    {
        $this->freezeTime();
        $hospital = Hospital::factory()->create(['name' => 'Hospital A']);
        $doctor = $this->doctor($hospital);
        $patient = User::factory()->patient()->create(['name' => 'Hospital A Patient']);
        $receptionist = User::factory()->receptionist()->create(['hospital_id' => $hospital->id]);
        $form = $this->actingAs($patient)->get(route('patient.appointments.create'))->assertOk()->assertSee('Hospital A');
        $this->saveReview('patient-booking', $form);
        $this->post(route('patient.appointments.store'), [
            'hospital_id' => $hospital->id, 'doctor_id' => $doctor->id,
            'appointment_date' => today()->toDateString(), 'reason' => 'Routine review',
            'status' => 'approved', 'patient_id' => $receptionist->id,
        ])->assertRedirect(route('patient.appointments.index'))->assertSessionHasNoErrors();
        $appointment = Appointment::sole();
        $this->assertSame('pending', $appointment->status);
        $this->assertSame($patient->id, $appointment->patient_id);
        $this->assertSame($hospital->id, $appointment->hospital_id);
        $this->assertSame('Routine review', $appointment->reason);
        $this->post(route('patient.appointments.check-in', $appointment))->assertSessionHasErrors('appointment');
        $this->assertSame('pending', $appointment->fresh()->status);
        $this->actingAs($doctor)->get(route('doctor.dashboard'))->assertViewHas('approvedAppointments', fn (LengthAwarePaginator $rows): bool => $rows->isEmpty());

        $desk = $this->actingAs($receptionist)->get(route('receptionist.dashboard'))->assertSee('Hospital A Patient')->assertSee('Approve');
        $this->saveReview('receptionist-desk', $desk);
        $this->post(route('receptionist.appointments.approve', $appointment))->assertRedirect()->assertSessionHas('status', 'Appointment approved.');
        $this->assertSame('approved', $appointment->fresh()->status);
        $this->assertDatabaseCount('consent_tokens', 0);
        $this->assertDatabaseCount('audit_log', 0);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $patient->id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $doctor->id]);
        $page = $this->actingAs($doctor)->get(route('doctor.dashboard'))->assertSee('Hospital A Patient')->assertSee('Hospital A')->assertSee('Routine review')->assertSee('Approved')
            ->assertViewHas('approvedAppointments', fn (LengthAwarePaginator $rows): bool => $rows->contains('id', $appointment->id));
        $this->saveReview('doctor-approved', $page);
        $this->actingAs($patient)->get(route('patient.appointments.index'))->assertSee('Approved', false);
        $this->post(route('patient.appointments.check-in', $appointment))->assertSessionHasNoErrors();
        $this->assertSame('checked_in', $appointment->fresh()->status);
    }

    public function test_patient_cannot_mix_a_hospital_and_unrelated_doctor(): void
    {
        $hospital = Hospital::factory()->create();
        $doctor = $this->doctor(Hospital::factory()->create());
        $this->actingAs(User::factory()->patient()->create())->post(route('patient.appointments.store'), [
            'hospital_id' => $hospital->id, 'doctor_id' => $doctor->id, 'appointment_date' => today()->toDateString(),
        ])->assertSessionHasErrors('doctor_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_hospital_selection_is_required_and_unassigned_doctors_cannot_be_booked(): void
    {
        $hospital = Hospital::factory()->create();
        $doctor = $this->doctor($hospital);
        $doctor->update(['hospital_id' => null]);
        $patient = User::factory()->patient()->create();
        $this->actingAs($patient)->get(route('patient.appointments.create'))->assertDontSee($doctor->name);
        $this->post(route('patient.appointments.store'), ['doctor_id' => $doctor->id, 'appointment_date' => today()->toDateString()])->assertSessionHasErrors('hospital_id');
        $this->post(route('patient.appointments.store'), ['hospital_id' => $hospital->id, 'doctor_id' => $doctor->id, 'appointment_date' => today()->toDateString()])->assertSessionHasErrors('doctor_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_receptionists_see_only_their_hospital_including_future_requests(): void
    {
        $this->freezeTime();
        $a = Hospital::factory()->create();
        $b = Hospital::factory()->create();
        $first = $this->appointment($this->doctor($a), ['appointment_date' => today()->addDays(3)]);
        $second = $this->appointment($this->doctor($b));
        foreach ([[$a, $first, $second], [$b, $second, $first]] as [$hospital, $own, $other]) {
            $user = User::factory()->receptionist()->create(['hospital_id' => $hospital->id]);
            $this->actingAs($user)->get(route('receptionist.dashboard'))->assertSee($own->patient->name)->assertDontSee($other->patient->name);
            $this->get(route('receptionist.appointments.index'))->assertSee($own->patient->name)->assertDontSee($other->patient->name);
            $this->get(route('receptionist.appointments.show', $own))->assertOk()->assertSee($own->patient->name);
        }
    }

    public function test_cross_hospital_direct_urls_cannot_read_approve_reject_or_check_in(): void
    {
        $a = Hospital::factory()->create();
        $appointment = $this->appointment($this->doctor(Hospital::factory()->create()));
        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $a->id]));
        $this->get(route('receptionist.appointments.show', $appointment))->assertNotFound();
        foreach (['approve', 'reject'] as $action) {
            $this->post(route('receptionist.appointments.'.$action, $appointment), ['hospital_id' => $appointment->hospital_id])->assertNotFound();
        }
        $this->post(route('receptionist.check-in', $appointment))->assertNotFound();
        $this->assertSame('pending', $appointment->fresh()->status);
        $this->assertDatabaseCount('notifications', 0);
    }

    #[TestWith(['approved', 'approve'])]
    #[TestWith(['approved', 'reject'])]
    #[TestWith(['rejected', 'approve'])]
    #[TestWith(['completed', 'approve'])]
    public function test_only_pending_requests_allow_a_status_decision(string $status, string $action): void
    {
        $hospital = Hospital::factory()->create();
        $appointment = $this->appointment($this->doctor($hospital), ['status' => $status]);
        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $hospital->id]))
            ->post(route('receptionist.appointments.'.$action, $appointment))->assertSessionHasErrors('appointment');
        $this->assertSame($status, $appointment->fresh()->status);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_rejection_is_scoped_and_cannot_be_changed_by_repeated_approval(): void
    {
        $hospital = Hospital::factory()->create();
        $appointment = $this->appointment($this->doctor($hospital));
        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $hospital->id]))
            ->post(route('receptionist.appointments.reject', $appointment))->assertSessionHas('status', 'Appointment rejected.');
        $this->assertSame('rejected', $appointment->fresh()->status);
        $this->assertDatabaseCount('notifications', 1);
        $this->post(route('receptionist.appointments.approve', $appointment))->assertSessionHasErrors('appointment');
        $this->assertDatabaseCount('notifications', 1);
        $this->actingAs($appointment->patient)->post(route('patient.appointments.check-in', $appointment))->assertSessionHasErrors('appointment');
    }

    public function test_repeated_approval_does_not_resend_notifications_or_change_status(): void
    {
        $hospital = Hospital::factory()->create();
        $appointment = $this->appointment($this->doctor($hospital));
        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $hospital->id]))
            ->post(route('receptionist.appointments.approve', $appointment))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('notifications', 2);
        $this->post(route('receptionist.appointments.approve', $appointment))->assertSessionHasErrors('appointment');
        $this->assertSame('approved', $appointment->fresh()->status);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_unassigned_receptionist_sees_a_message_and_cannot_manage_any_appointment(): void
    {
        $appointment = Appointment::factory()->create(['hospital_id' => null]);
        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => null]))
            ->get(route('receptionist.dashboard'))->assertSee('No hospital is assigned')->assertDontSee($appointment->patient->name);
        $this->get(route('receptionist.walk-in'))->assertForbidden();
        $this->get(route('receptionist.appointments.index'))->assertForbidden();
        $this->get(route('receptionist.appointments.show', $appointment))->assertForbidden();
        foreach (['approve', 'reject'] as $action) {
            $this->post(route('receptionist.appointments.'.$action, $appointment))->assertForbidden();
        }
        $this->post(route('receptionist.check-in', $appointment))->assertForbidden();
    }

    public function test_doctor_dashboard_keeps_approved_and_pending_visits_distinct_and_doctor_scoped(): void
    {
        $hospital = Hospital::factory()->create();
        $doctor = $this->doctor($hospital);
        $own = $this->appointment($doctor, ['status' => 'approved']);
        $pending = $this->appointment($doctor, ['status' => 'pending']);
        $other = $this->appointment($this->doctor($hospital), ['status' => 'approved']);
        $this->actingAs($doctor)->get(route('doctor.dashboard'))->assertSee($own->patient->name)->assertSee($pending->patient->name)
            ->assertDontSee($other->patient->name)->assertViewHas('approvedAppointments', fn (LengthAwarePaginator $rows): bool => $rows->total() === 1 && $rows->first()->id === $own->id);
        $this->get(route('doctor.prescriptions.create', $other))->assertForbidden();
    }

    public function test_patients_see_only_their_appointments_and_cannot_check_in_someone_elses(): void
    {
        $hospital = Hospital::factory()->create();
        $first = $this->appointment($this->doctor($hospital), ['status' => 'approved']);
        $other = $this->appointment($this->doctor($hospital), ['status' => 'approved']);
        $this->actingAs($first->patient)->get(route('patient.appointments.index'))->assertSee($first->doctor->name)->assertDontSee($other->doctor->name);
        $this->post(route('patient.appointments.check-in', $other))->assertForbidden();
        $this->assertSame('approved', $other->fresh()->status);
    }

    #[TestWith(['patient'])]
    #[TestWith(['doctor'])]
    #[TestWith(['admin'])]
    #[TestWith(['lab_assistant'])]
    public function test_other_roles_cannot_reach_receptionist_appointment_management(string $role): void
    {
        $appointment = $this->appointment($this->doctor(Hospital::factory()->create()));
        $this->actingAs(User::factory()->create(['role' => $role, 'hospital_id' => $appointment->hospital_id]));
        $this->get(route('receptionist.appointments.index'))->assertForbidden();
        $this->get(route('receptionist.appointments.show', $appointment))->assertForbidden();
        $this->post(route('receptionist.appointments.approve', $appointment))->assertForbidden();
        $this->post(route('receptionist.appointments.reject', $appointment))->assertForbidden();
    }

    public function test_guest_cannot_read_or_decide_appointment_requests(): void
    {
        $appointment = $this->appointment($this->doctor(Hospital::factory()->create()));
        $this->get(route('receptionist.appointments.index'))->assertRedirect(route('login'));
        $this->post(route('receptionist.appointments.approve', $appointment))->assertRedirect(route('login'));
    }

    public function test_walk_in_stays_pending_until_approved_and_only_uses_the_receptionists_hospital(): void
    {
        $hospital = Hospital::factory()->create();
        $doctor = $this->doctor($hospital);
        $other = $this->doctor(Hospital::factory()->create());
        $patient = User::factory()->patient()->create();
        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $hospital->id]))
            ->get(route('receptionist.walk-in'))->assertSee($doctor->name)->assertDontSee($other->name);
        $data = ['patient_email' => $patient->email, 'doctor_id' => $other->id, 'appointment_date' => today()->toDateString(), 'priority_type' => 'normal'];
        $this->post(route('receptionist.walk-in.store'), $data)->assertNotFound();
        $data['doctor_id'] = $doctor->id;
        $this->post(route('receptionist.walk-in.store'), $data)->assertRedirect(route('receptionist.dashboard'));
        $appointment = Appointment::sole();
        $this->assertSame('pending', $appointment->status);
        $this->assertSame($hospital->id, $appointment->hospital_id);
        $this->post(route('receptionist.appointments.approve', $appointment))->assertSessionHasNoErrors();
        $this->post(route('receptionist.check-in', $appointment))->assertSessionHasNoErrors();
        $this->assertSame('checked_in', $appointment->fresh()->status);
    }

    public function test_historical_records_stay_unassigned_until_an_admin_explicitly_assigns_them(): void
    {
        $hospital = Hospital::factory()->create();
        $doctor = $this->doctor($hospital);
        $doctor->update(['hospital_id' => null]);
        $legacy = Appointment::factory()->create(['doctor_id' => $doctor->id, 'hospital_id' => null, 'status' => 'pending']);
        $this->actingAs($doctor)->get(route('doctor.dashboard'))->assertSee($legacy->patient->name);
        $this->actingAs($legacy->patient)->get(route('patient.appointments.index'))->assertSee($doctor->name);
        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $hospital->id]))
            ->get(route('receptionist.dashboard'))->assertDontSee($legacy->patient->name);
        $this->assertNull($legacy->fresh()->hospital_id);
        $this->assertNull($doctor->fresh()->hospital_id);
        $admin = User::factory()->admin()->create();
        $page = $this->actingAs($admin)->get(route('admin.hospitals.index'))->assertOk()->assertSee($legacy->patient->name);
        $this->saveReview('admin-hospitals', $page);
        $this->post(route('admin.hospitals.staff.assign', $doctor), ['hospital_id' => $hospital->id])->assertSessionHasNoErrors();
        $this->assertNull($legacy->fresh()->hospital_id);
        $this->post(route('admin.hospitals.appointments.assign', $legacy), ['hospital_id' => $hospital->id])->assertSessionHasNoErrors();
        $this->assertSame($hospital->id, $legacy->fresh()->hospital_id);
        $this->assertSame('pending', $legacy->fresh()->status);
    }

    public function test_admin_assignment_cannot_mix_an_appointments_doctor_and_hospital_or_overwrite_history(): void
    {
        $hospital = Hospital::factory()->create();
        $other = Hospital::factory()->create();
        $doctor = $this->doctor($hospital);
        $appointment = Appointment::factory()->create(['doctor_id' => $doctor->id, 'hospital_id' => null]);
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.hospitals.appointments.assign', $appointment), ['hospital_id' => $other->id])->assertSessionHasErrors('hospital_id');
        $this->assertNull($appointment->fresh()->hospital_id);
        $this->post(route('admin.hospitals.appointments.assign', $appointment), ['hospital_id' => $hospital->id])->assertSessionHasNoErrors();
        $this->post(route('admin.hospitals.appointments.assign', $appointment), ['hospital_id' => $other->id])->assertSessionHasErrors('hospital_id');
        $this->assertSame($hospital->id, $appointment->fresh()->hospital_id);
    }

    public function test_active_future_appointments_prevent_reassigning_their_doctor_to_another_hospital(): void
    {
        $this->freezeTime();
        $hospital = Hospital::factory()->create();
        $doctor = $this->doctor($hospital);
        $this->appointment($doctor, ['status' => 'approved', 'appointment_date' => today()->addDay()]);
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.hospitals.staff.assign', $doctor), ['hospital_id' => Hospital::factory()->create()->id])->assertSessionHasErrors('hospital_id');
        $this->assertSame($hospital->id, $doctor->fresh()->hospital_id);
    }

    public function test_only_admins_can_create_hospitals_and_assign_staff(): void
    {
        $hospital = Hospital::factory()->create();
        $doctor = $this->doctor($hospital);
        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $hospital->id]))
            ->get(route('admin.hospitals.index'))->assertForbidden();
        $this->post(route('admin.hospitals.store'), ['name' => 'Unauthorized hospital'])->assertForbidden();
        $this->post(route('admin.hospitals.staff.assign', $doctor), ['hospital_id' => null])->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.hospitals.store'), ['name' => 'New Hospital'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hospitals', ['name' => 'New Hospital']);
        $patient = User::factory()->patient()->create();
        $this->post(route('admin.hospitals.staff.assign', $patient), ['hospital_id' => $hospital->id])->assertNotFound();
    }

    public function test_public_registration_cannot_self_assign_a_hospital(): void
    {
        $this->post('/register', ['name' => 'New Patient', 'email' => 'hospital-forgery@example.test', 'password' => 'password', 'password_confirmation' => 'password', 'hospital_id' => Hospital::factory()->create()->id, 'role' => 'receptionist'])->assertSessionHasNoErrors();
        $user = User::where('email', 'hospital-forgery@example.test')->sole();
        $this->assertSame('patient', $user->role);
        $this->assertNull($user->hospital_id);
    }

    public function test_approval_migration_refuses_to_rollback_configured_hospital_data(): void
    {
        $hospital = Hospital::factory()->create();
        $migration = require database_path('migrations/2026_11_23_132100_add_hospital_appointment_approval_workflow.php');
        try {
            $migration->down();
            $this->fail('A populated hospital migration must not be rolled back destructively.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('forward migration', $exception->getMessage());
        }
        $this->assertModelExists($hospital);
    }

    public function test_hospital_names_and_appointment_reasons_are_escaped_in_staff_views(): void
    {
        $hospital = Hospital::factory()->create(['name' => 'Hospital <script>alert(1)</script>']);
        $doctor = $this->doctor($hospital);
        $reason = '<script>alert(2)</script>';
        $this->appointment($doctor, ['reason' => $reason, 'status' => 'approved']);

        $this->actingAs($doctor)->get(route('doctor.dashboard'))->assertSee($hospital->name)->assertSee($reason)
            ->assertDontSee($hospital->name, false)->assertDontSee($reason, false);
        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $hospital->id]))
            ->get(route('receptionist.appointments.index'))->assertSee($reason)->assertDontSee($reason, false);
        $this->actingAs(User::factory()->patient()->create())->get(route('patient.appointments.create'))
            ->assertSee($hospital->name)->assertDontSee($hospital->name, false);
    }

    public function test_reason_validation_rejects_oversized_text_without_creating_a_request(): void
    {
        $hospital = Hospital::factory()->create();
        $doctor = $this->doctor($hospital);
        $this->actingAs(User::factory()->patient()->create())->post(route('patient.appointments.store'), [
            'hospital_id' => $hospital->id, 'doctor_id' => $doctor->id,
            'appointment_date' => today()->toDateString(), 'reason' => str_repeat('a', 1001),
        ])->assertSessionHasErrors('reason');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_demo_seeding_assigns_new_staff_but_does_not_backfill_existing_staff(): void
    {
        $receptionist = User::factory()->receptionist()->create(['email' => 'reception@medixa.test', 'hospital_id' => null]);
        $this->seed(MedixaSeeder::class);
        $hospital = Hospital::where('name', 'Medixa Hospital')->sole();
        $this->assertSame(6, User::doctors()->where('hospital_id', $hospital->id)->count());
        $this->assertNull($receptionist->fresh()->hospital_id);
    }

    private function doctor(Hospital $hospital): User
    {
        $doctor = User::factory()->doctor()->create(['hospital_id' => $hospital->id]);
        DoctorProfile::create(['user_id' => $doctor->id, 'department_id' => Department::firstOrCreate(['name' => 'Medicine'])->id, 'qualification' => 'MBBS', 'designation' => 'Consultant']);

        return $doctor;
    }

    /** @param array<string, mixed> $overrides */
    private function appointment(User $doctor, array $overrides = []): Appointment
    {
        return Appointment::factory()->create(array_replace(['doctor_id' => $doctor->id, 'hospital_id' => $doctor->hospital_id], $overrides));
    }

    private function saveReview(string $name, TestResponse $response): void
    {
        if ($directory = getenv('MEDIXA_HOSPITAL_REVIEW_DIRECTORY')) {
            file_put_contents($directory.DIRECTORY_SEPARATOR.$name.'.html', $response->getContent());
        }
    }
}
