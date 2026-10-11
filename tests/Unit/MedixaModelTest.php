<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\LiveQueue;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedixaModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_roles_and_scopes(): void
    {
        $patient = User::factory()->patient()->create();
        PatientProfile::create([
            'user_id' => $patient->id,
            'dob' => '1990-05-15',
            'blood_group' => 'O_POSITIVE',
        ]);
        $doctor = User::factory()->doctor()->create();

        $patient->refresh();

        $this->assertTrue($patient->isPatient());
        $this->assertFalse($patient->isDoctor());
        $this->assertNotNull($patient->patientProfile);

        $this->assertTrue($doctor->isDoctor());
        $this->assertFalse($doctor->isPatient());
        $this->assertTrue(User::patients()->whereKey($patient->id)->exists());
        $this->assertFalse(User::doctors()->whereKey($patient->id)->exists());
        $this->assertTrue(User::doctors()->whereKey($doctor->id)->exists());
        $this->assertFalse(User::patients()->whereKey($doctor->id)->exists());
    }

    public function test_department_has_doctors(): void
    {
        $department = Department::create(['name' => 'Cardiology']);
        $doctor = User::factory()->doctor()->create();
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => $department->id,
            'qualification' => 'MBBS',
            'designation' => 'Consultant',
        ]);

        $department->load('doctorProfiles');

        $this->assertInstanceOf(Department::class, $department);
        $this->assertCount(1, $department->doctorProfiles);
        $this->assertSame($doctor->id, $department->doctorProfiles->first()->user_id);
    }

    public function test_doctor_profile_relationships(): void
    {
        $department = Department::create(['name' => 'Neurology']);
        $doctor = User::factory()->doctor()->create();
        $profile = DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => $department->id,
            'qualification' => 'MBBS',
            'designation' => 'Consultant',
        ]);
        LiveQueue::create([
            'doctor_id' => $doctor->id,
            'current_running_serial' => 3,
            'doctor_present_status' => true,
        ]);

        $profile->load(['user', 'department', 'liveQueue']);

        $this->assertInstanceOf(User::class, $profile->user);
        $this->assertTrue($profile->user->is($doctor));
        $this->assertInstanceOf(Department::class, $profile->department);
        $this->assertTrue($profile->department->is($department));
        $this->assertInstanceOf(LiveQueue::class, $profile->liveQueue);
        $this->assertSame(3, $profile->liveQueue->current_running_serial);
    }
}
