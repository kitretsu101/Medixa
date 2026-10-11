<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_successfully_with_departments(): void
    {
        $department = Department::create(['name' => 'Cardiology']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Medixa', false)
            ->assertSee($department->name, false);
    }

    public function test_doctors_index_renders_and_supports_department_filter(): void
    {
        $department = Department::create(['name' => 'Cardiology']);
        $other = Department::create(['name' => 'Neurology']);
        $doctor = User::factory()->doctor()->create(['name' => 'Dr. Public Cardiologist']);
        $elsewhere = User::factory()->doctor()->create(['name' => 'Dr. Public Neurologist']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => $department->id,
            'qualification' => 'MBBS',
            'designation' => 'Consultant',
        ]);
        DoctorProfile::create([
            'user_id' => $elsewhere->id,
            'department_id' => $other->id,
            'qualification' => 'MBBS',
            'designation' => 'Consultant',
        ]);

        $this->get(route('doctors.index'))
            ->assertOk()
            ->assertSee('Find a Doctor', false)
            ->assertSee('Dr. Public Cardiologist', false)
            ->assertSee('Dr. Public Neurologist', false);

        $this->get(route('doctors.index', ['department_id' => $department->id]))
            ->assertOk()
            ->assertSee('Dr. Public Cardiologist', false)
            ->assertDontSee('Dr. Public Neurologist', false);
    }

    public function test_doctor_profile_page_renders_successfully(): void
    {
        $department = Department::create(['name' => 'Cardiology']);
        $doctor = User::factory()->doctor()->create(['name' => 'Dr. Public Profile']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => $department->id,
            'qualification' => 'FRCS',
            'designation' => 'Senior Surgeon',
        ]);

        $this->get(route('doctors.show', $doctor))
            ->assertOk()
            ->assertSee('Dr. Public Profile', false)
            ->assertSee('FRCS', false);
    }

    public function test_department_detail_page_renders_successfully(): void
    {
        $department = Department::create(['name' => 'Cardiology']);
        $doctor = User::factory()->doctor()->create(['name' => 'Dr. Department Listing']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => $department->id,
            'qualification' => 'MBBS',
            'designation' => 'Consultant',
        ]);

        $this->get(route('departments.show', $department))
            ->assertOk()
            ->assertSee('Cardiology', false)
            ->assertSee('Dr. Department Listing', false);
    }
}
