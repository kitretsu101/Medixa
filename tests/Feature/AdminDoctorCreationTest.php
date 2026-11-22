<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AdminDoctorCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_doctor_login_and_linked_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $department = Department::create(['name' => 'Cardiology', 'description' => 'Heart care']);
        $this->actingAs($admin)->get(route('admin.doctors.create'))
            ->assertOk()->assertSee('Initial Password');

        $this->post(route('admin.doctors.store'), [
            'name' => 'Created Doctor', 'email' => 'created-doctor@example.com',
            'password' => 'Initial-password-123', 'department_id' => $department->id,
            'qualification' => 'MBBS', 'designation' => 'Consultant',
        ])->assertRedirect(route('admin.doctors.index'));

        $doctor = User::where('email', 'created-doctor@example.com')->firstOrFail();
        $this->assertSame('doctor', $doctor->role);
        $this->assertTrue(Hash::check('Initial-password-123', $doctor->password));
        $this->assertDatabaseHas('doctor_profiles', [
            'user_id' => $doctor->id, 'department_id' => $department->id,
            'qualification' => 'MBBS', 'designation' => 'Consultant',
        ]);
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $doctor->email, 'password' => 'Initial-password-123'])
            ->assertRedirect(route('doctor.dashboard', absolute: false));
        $this->assertAuthenticatedAs($doctor);
    }

    #[TestWith(['patient'])]
    #[TestWith(['doctor'])]
    #[TestWith(['receptionist'])]
    #[TestWith(['lab_assistant'])]
    public function test_non_admins_cannot_create_doctor_accounts(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]));
        $this->get(route('admin.doctors.create'))->assertForbidden();
        $this->post(route('admin.doctors.store'), [])->assertForbidden();
    }
}
