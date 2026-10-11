<?php

namespace Tests\Feature;

use App\Contracts\AiCompletionProvider;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\LabReport;
use App\Models\PatientProfile;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Ai\AiCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.ai.provider', 'openai');
        Config::set('services.ai.key', null);
        Config::set('services.openai.api_key', null);
        Http::preventStrayRequests();
    }

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
        Config::set('services.ai.key', 'test-openai-key-fake');
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
        $this->assertStringNotContainsString('test-openai-key-fake', $response->getContent());

        // Inspect the exact request sent over the wire to OpenAI
        Http::assertSent(function (ClientRequest $request) use ($patient, $response) {
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
            $this->assertStringNotContainsString('test-openai-key-fake', $systemContent);
            $this->assertStringNotContainsString('test-openai-key-fake', $userContent);

            $authorization = implode(' ', $request->header('Authorization'));
            $this->assertStringContainsString('Bearer test-openai-key-fake', $authorization);

            // The provider correlation id is not the session secret or the patient id.
            $this->assertStringStartsWith('session:', $userIdentifier);
            $this->assertNotSame('session:'.$patient->id, $userIdentifier);
            $this->assertStringNotContainsString((string) $response->json('session_token'), $userIdentifier);
            $this->assertStringNotContainsString($patient->email, $userIdentifier);

            return true;
        });
    }

    public function test_model_booking_marker_does_not_create_an_appointment(): void
    {
        Config::set('services.ai.key', 'test-openai-key-fake');
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
            'message' => 'Hello from the waiting room',
        ]);

        $response->assertOk()
            ->assertJsonPath('booking', null);

        $this->assertSame(0, Appointment::query()->count());
        $this->assertStringNotContainsString('[BOOKING_INTENT]', (string) $response->json('reply'));
    }

    public function test_unauthenticated_booking_confirmation_requires_patient_login(): void
    {
        $doctor = User::factory()->doctor()->create(['name' => 'Dr. Sarah Smith']);
        $dept = Department::firstOrCreate(['name' => 'Pediatrics']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => $dept->id,
            'qualification' => 'MD, Pediatrics',
            'designation' => 'Consultant',
        ]);

        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'Confirm this routine appointment',
            'confirm' => [
                'doctor_id' => $doctor->id,
                'appointment_date' => now()->addDay()->toDateString(),
                'confirmed' => true,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('booking.success', false)
            ->assertJsonPath('booking.message', 'Please log in to your patient account to book an appointment.');

        $this->assertSame(0, Appointment::count());
    }

    public function test_guest_can_chat_in_bangla_without_calling_a_provider(): void
    {
        Department::create(['name' => 'Cardiology']);

        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'কোন বিভাগ আছে?',
            'locale' => 'bn',
        ]);

        $response->assertOk()
            ->assertJsonPath('locale', 'bn')
            ->assertJsonPath('error', false);

        $this->assertStringContainsString('আমাদের বিভাগগুলো', (string) $response->json('reply'));
        $this->assertStringContainsString('Cardiology', (string) $response->json('reply'));
        Http::assertNothingSent();
    }

    public function test_message_must_be_present_and_within_the_length_limit(): void
    {
        $this->postJson(route('chatbot.message'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);

        $this->postJson(route('chatbot.message'), [
            'message' => str_repeat('a', 2001),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);

        $this->postJson(route('chatbot.message'), [
            'message' => 'Hello',
            'locale' => 'fr',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['locale']);
    }

    public function test_mocked_provider_answers_without_receiving_clinical_records(): void
    {
        $patient = User::factory()->patient()->create(['name' => 'Chart Patient Unique']);
        $doctor = User::factory()->doctor()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        PatientProfile::create([
            'user_id' => $patient->id,
            'medical_history' => 'OnlyForThisHistory-Gamma773',
        ]);

        Prescription::factory()->create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'OnlyForThisChart-Alpha991',
        ]);

        LabReport::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'report_title' => 'OnlyForThisLab-Beta882',
        ]);

        $provider = new class implements AiCompletionProvider
        {
            /** @var list<array{0: string, 1: string, 2: string}> */
            public array $calls = [];

            public function complete(string $systemPrompt, string $userMessage, string $sessionToken): AiCompletion
            {
                $this->calls[] = [$systemPrompt, $userMessage, $sessionToken];

                return new AiCompletion('General hospital information.', 'ok');
            }
        };

        $this->app->instance(AiCompletionProvider::class, $provider);

        $response = $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'Which departments are open?',
            'locale' => 'en',
        ]);

        $response->assertOk()
            ->assertJsonPath('reply', 'General hospital information.')
            ->assertJsonPath('error', false);

        $this->assertCount(1, $provider->calls);
        [$systemPrompt, $userMessage, $sessionToken] = $provider->calls[0];
        $combined = $systemPrompt.$userMessage.$sessionToken;

        $this->assertStringContainsString('Respond in English.', $systemPrompt);
        $this->assertStringNotContainsString('Chart Patient Unique', $combined);
        $this->assertStringNotContainsString('OnlyForThisHistory-Gamma773', $combined);
        $this->assertStringNotContainsString('OnlyForThisChart-Alpha991', $combined);
        $this->assertStringNotContainsString('OnlyForThisLab-Beta882', $combined);
        $this->assertNotSame((string) $patient->id, $sessionToken);
        Http::assertNothingSent();
    }

    public function test_provider_failure_returns_a_retryable_error_without_the_api_key(): void
    {
        Config::set('services.ai.key', 'test-key-not-real');

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(['error' => 'unavailable'], 503),
        ]);

        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'Which doctors are available?',
        ]);

        $response->assertOk()
            ->assertJsonPath('error', true)
            ->assertJsonPath('retryable', true)
            ->assertJsonPath('booking', null)
            ->assertJsonPath('reply', 'The assistant could not answer just now. Please try again. A missing answer does not mean you are well. Seek care if you might be seriously unwell.');

        $this->assertStringNotContainsString('test-key-not-real', (string) $response->getContent());
    }

    public function test_provider_timeout_returns_a_retryable_error(): void
    {
        Config::set('services.ai.key', 'test-key-not-real');

        Http::fake(function (): void {
            throw new ConnectionException('timed out');
        });

        $this->postJson(route('chatbot.message'), [
            'message' => 'বিভাগ',
            'locale' => 'bn',
        ])->assertOk()
            ->assertJsonPath('error', true)
            ->assertJsonPath('retryable', true)
            ->assertJsonPath('reply', 'সহকারী এই মুহূর্তে উত্তর দিতে পারছে না। আবার চেষ্টা করুন। উত্তর না পাওয়া মানে আপনি সুস্থ, এমন নয়। গুরুতর অসুস্থতায় চিকিৎসা নিন।');
    }

    public function test_unknown_provider_uses_the_local_reply_and_does_not_call_openai(): void
    {
        Config::set('services.ai.provider', 'example-provider');
        Config::set('services.ai.key', 'test-key-not-real');

        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'Hello there',
        ]);

        $response->assertOk()
            ->assertJsonPath('error', false);

        $this->assertStringContainsString('does not replace a healthcare professional', (string) $response->json('reply'));
        Http::assertNothingSent();
    }

    public function test_public_and_patient_pages_show_the_assistant_and_doctor_pages_do_not(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('This assistant shares general information and does not replace a healthcare professional', false)
            ->assertSee('id="medixa-chat-widget"', false);

        $patient = User::factory()->patient()->create();

        $this->actingAs($patient)->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('Ask the assistant', false)
            ->assertSee('id="medixa-chat-widget"', false);

        $doctor = User::factory()->doctor()->create();
        $department = Department::create(['name' => 'Cardiology Desk']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => $department->id,
            'qualification' => 'MBBS',
            'designation' => 'Consultant',
        ]);

        $this->actingAs($doctor)->get(route('doctor.dashboard'))
            ->assertOk()
            ->assertDontSee('id="medixa-chat-widget"', false)
            ->assertDontSee('Ask the assistant', false);
    }

    public function test_staff_portals_stay_available_and_do_not_show_the_assistant(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Portal', false)
            ->assertDontSee('id="medixa-chat-widget"', false);

        $this->actingAs(User::factory()->create(['role' => 'lab_assistant']))
            ->get(route('lab.dashboard'))
            ->assertOk()
            ->assertSee('Lab Assistant Portal', false)
            ->assertDontSee('id="medixa-chat-widget"', false);

        $this->actingAs(User::factory()->receptionist()->create())
            ->get(route('receptionist.dashboard'))
            ->assertOk()
            ->assertSee("Today's desk", false)
            ->assertDontSee('id="medixa-chat-widget"', false);
    }

    public function test_a_non_string_message_is_rejected(): void
    {
        $this->postJson(route('chatbot.message'), [
            'message' => ['not', 'a', 'string'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    }

    public function test_chat_requests_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->postJson(route('chatbot.message'), [
                'message' => 'Hello',
            ])->assertOk();
        }

        $this->postJson(route('chatbot.message'), [
            'message' => 'Hello',
        ])->assertTooManyRequests();
    }
}
