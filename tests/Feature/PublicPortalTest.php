<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DoctorProfile;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    public function test_home_page_renders_successfully_with_departments(): void
    {
        $department = Department::first();

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Medixa');
        if ($department) {
            $response->assertSee($department->name);
        }
    }

    public function test_doctors_index_renders_and_supports_department_filter(): void
    {
        $response = $this->get(route('doctors.index'));
        $response->assertStatus(200);
        $response->assertSee('Find a Doctor');

        $department = Department::first();
        if ($department) {
            $filteredResponse = $this->get(route('doctors.index', ['department_id' => $department->id]));
            $filteredResponse->assertStatus(200);
        }
    }

    public function test_doctor_profile_page_renders_successfully(): void
    {
        $doctorProfile = DoctorProfile::with(['user', 'department', 'liveQueue'])->first();

        if ($doctorProfile) {
            $response = $this->get(route('doctors.show', $doctorProfile->id));
            $response->assertStatus(200);
            $response->assertSee($doctorProfile->user->name);
            $response->assertSee($doctorProfile->qualification);
        } else {
            $this->markTestSkipped('No seeded doctors found.');
        }
    }

    public function test_department_detail_page_renders_successfully(): void
    {
        $department = Department::with('doctorProfiles.user')->first();

        if ($department) {
            $response = $this->get(route('departments.show', $department->id));
            $response->assertStatus(200);
            $response->assertSee($department->name);
        } else {
            $this->markTestSkipped('No seeded departments found.');
        }
    }
}
