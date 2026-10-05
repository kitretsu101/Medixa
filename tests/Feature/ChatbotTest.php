<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_endpoint_accepts_free_text_and_returns_response(): void
    {
        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'Hello, can you help me find a doctor?',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'reply',
                'session_token',
                'booking',
            ]);

        $this->assertNotEmpty($response->json('reply'));
        $this->assertNotEmpty($response->json('session_token'));
    }

    public function test_chatbot_is_identity_blind_and_never_sends_patient_identifying_data_to_ai(): void
    {
        Config::set('services.openai.api_key', 'test-openai-key-fake');

        // Create an authenticated patient with full private medical profile
        $patient = User::factory()->patient()->create([
            'name' => 'Alice Private Patient',
            'email' => 'alice.private@confidential.com',
        ]);

        PatientProfile::create([
            'user_id' => $patient->id,
            'dob' => '1990-05-15',
            'blood_group' => 'O_POSITIVE',
            'emergency_contact' => '+1-555-PRIVATE-PHONE',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'We have Cardiology and Neurology departments available for consultation.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'What departments are open today?',
        ]);

        $response->assertOk();

        // Inspect the exact request sent over the wire to OpenAI
        Http::assertSent(function (ClientRequest $request) use ($patient) {
            $body = json_decode($request->body(), true);
            $systemContent = $body['messages'][0]['content'] ?? '';
            $userContent = $body['messages'][1]['content'] ?? '';
            $userIdentifier = $body['user'] ?? '';

            // Verify strict identity-blindness: NO patient details sent
            $this->assertStringNotContainsString('Alice Private Patient', $systemContent);
            $this->assertStringNotContainsString('alice.private@confidential.com', $systemContent);
            $this->assertStringNotContainsString('+1-555-PRIVATE-PHONE', $systemContent);
            $this->assertStringNotContainsString('O_POSITIVE', $systemContent);

            $this->assertStringNotContainsString('Alice Private Patient', $userContent);
            $this->assertStringNotContainsString('alice.private@confidential.com', $userContent);

            // Verify user identifier is scoped session token only (not patient ID/email)
            $this->assertStringStartsWith('session:', $userIdentifier);
            $this->assertNotSame('session:'.$patient->id, $userIdentifier);
            $this->assertStringNotContainsString($patient->email, $userIdentifier);

            return true;
        });
    }

    public function test_booking_intent_creates_appointment_using_server_session_user(): void
    {
        Config::set('services.openai.api_key', 'test-openai-key-fake');

        $doctor = User::factory()->doctor()->create(['name' => 'Dr. Robert Liston']);
        $dept = Department::firstOrCreate(['name' => 'General Surgery']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => $dept->id,
            'qualification' => 'FRCS',
            'designation' => 'Senior Surgeon',
        ]);

        $patient = User::factory()->patient()->create(['name' => 'John Patient']);

        $bookingDate = now()->addDays(2)->toDateString();

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "I have scheduled your appointment with Dr. Robert Liston on {$bookingDate}. [BOOKING_INTENT]{\"doctor_name\":\"Dr. Robert Liston\",\"date\":\"{$bookingDate}\"}[/BOOKING_INTENT]",
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'Please confirm booking with Dr. Robert Liston',
        ]);

        $response->assertOk()
            ->assertJson([
                'booking' => [
                    'success' => true,
                ],
            ]);

        // Appointment created in DB with patient ID from the session!
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $bookingDate,
            'status' => 'pending',
        ]);

        // The intent marker [BOOKING_INTENT] is cleanly stripped from visible reply
        $this->assertStringNotContainsString('[BOOKING_INTENT]', $response->json('reply'));
    }

    public function test_unauthenticated_booking_intent_requires_patient_login(): void
    {
        Config::set('services.openai.api_key', 'test-openai-key-fake');

        $doctor = User::factory()->doctor()->create(['name' => 'Dr. Sarah Smith']);
        $dept = Department::firstOrCreate(['name' => 'Pediatrics']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => $dept->id,
            'qualification' => 'MD, Pediatrics',
            'designation' => 'Consultant',
        ]);

        $bookingDate = now()->addDays(1)->toDateString();

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "Booking details ready. [BOOKING_INTENT]{\"doctor_name\":\"Dr. Sarah Smith\",\"date\":\"{$bookingDate}\"}[/BOOKING_INTENT]",
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Guest user calls endpoint
        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'Book appointment with Dr Sarah',
        ]);

        $response->assertOk()
            ->assertJson([
                'booking' => [
                    'success' => false,
                    'message' => 'Please log in to your patient account to book an appointment.',
                ],
            ]);

        $this->assertSame(0, Appointment::count());
    }
}
