<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $this->assertSame(0, (new \DOMXPath($document))->query('//*[@name="role"]')->length);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('patient.dashboard', absolute: false));
    }

    #[TestWith(['admin'])]
    #[TestWith(['doctor'])]
    public function test_forged_registration_role_is_always_patient(string $role): void
    {
        $response = $this->post('/register', [
            'name' => 'Public Patient',
            'email' => 'public@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => $role,
        ]);

        $patient = User::where('email', 'public@example.com')->firstOrFail();
        $this->assertSame('patient', $patient->role);
        $this->assertAuthenticatedAs($patient);
        $this->assertDatabaseMissing('doctor_profiles', ['user_id' => $patient->id]);
        $response->assertRedirect(route('patient.dashboard', absolute: false));
    }
}
