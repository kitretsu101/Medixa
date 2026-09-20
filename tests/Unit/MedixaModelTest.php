<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\LiveQueue;
use App\Models\User;
use Tests\TestCase;

class MedixaModelTest extends TestCase
{
    public function test_user_roles_and_scopes(): void
    {
        $patient = User::where('role', 'patient')->first();
        $doctor = User::where('role', 'doctor')->first();

        if ($patient) {
            $this->assertTrue($patient->isPatient());
            $this->assertFalse($patient->isDoctor());
            $this->assertNotNull($patient->patientProfile);
        }

        if ($doctor) {
            $this->assertTrue($doctor->isDoctor());
            $this->assertFalse($doctor->isPatient());
            $this->assertNotNull($doctor->doctorProfile);
        }
    }

    public function test_department_has_doctors(): void
    {
        $department = Department::with('doctorProfiles')->first();
        if ($department) {
            $this->assertInstanceOf(Department::class, $department);
            $this->assertNotEmpty($department->doctorProfiles);
        }
    }

    public function test_doctor_profile_relationships(): void
    {
        $doctorProfile = DoctorProfile::with(['user', 'department', 'liveQueue'])->first();
        if ($doctorProfile) {
            $this->assertInstanceOf(User::class, $doctorProfile->user);
            $this->assertInstanceOf(Department::class, $doctorProfile->department);
            if ($doctorProfile->liveQueue) {
                $this->assertInstanceOf(LiveQueue::class, $doctorProfile->liveQueue);
            }
        }
    }
}
