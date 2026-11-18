<?php

namespace Tests\Feature;

use App\Contracts\AiCompletionProvider;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\MedicalKnowledgeArticle;
use App\Models\User;
use App\Services\Ai\AiCompletion;
use App\Services\SymptomTriageService;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SymptomTriageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_symptoms_offer_only_matching_department_before_the_existing_six_offer_limit(): void
    {
        $this->blockProvider();
        $this->doctor('Orthopedics', 'A First Doctor');
        $cardiologist = $this->doctor('Cardiology', 'Z Last Doctor');
        $patient = User::factory()->patient()->create();
        $other = User::factory()->patient()->create(['name' => 'Private Patient 811']);
        Appointment::factory()->completed()->create([
            'patient_id' => $other->id, 'doctor_id' => $cardiologist->id,
            'appointment_date' => today()->toDateString(), 'serial_number' => 1,
            'priority_type' => 'normal',
        ]);

        $response = $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'I have palpitations. Can I book an appointment?',
        ]);

        $response->assertOk()->assertJsonPath('booking', null);
        $this->assertStringContainsString('Cardiology department', $response->json('reply'));
        $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $response->json('reply'));
        $this->assertStringContainsString('Nothing is booked until you confirm one', $response->json('reply'));
        $this->assertNotEmpty($response->json('offers'));
        foreach ($response->json('offers') as $offer) {
            $this->assertSame('Cardiology', $offer['department']);
            $this->assertSame($cardiologist->id, $offer['doctor_id']);
        }
        $this->assertSame(17, $response->json('offers.0.slots_remaining'));
        $this->assertSame(['reply', 'session_token', 'booking', 'error', 'retryable', 'locale', 'sources', 'offers'], array_keys($response->json()));
        $this->assertStringNotContainsString('Private Patient 811', $response->getContent());
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseCount('audit_log', 0);
        $this->assertDatabaseCount('consent_tokens', 0);
        Http::assertNothingSent();
    }

    public function test_a_triage_offer_uses_the_existing_explicit_booking_confirmation(): void
    {
        $this->blockProvider();
        $doctor = $this->doctor('Orthopedics');
        $patient = User::factory()->patient()->create();
        $offerResponse = $this->actingAs($patient)->postJson(route('chatbot.message'), ['message' => 'I have knee pain']);
        $offer = $offerResponse->json('offers.0');
        $this->assertNotNull($offer);
        $this->assertDatabaseCount('appointments', 0);

        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'Confirm this routine appointment',
            'session_token' => $offerResponse->json('session_token'),
            'confirm' => ['doctor_id' => $offer['doctor_id'], 'appointment_date' => $offer['date'], 'confirmed' => true],
        ]);

        $response->assertOk()->assertJsonPath('booking.success', true);
        $this->assertDatabaseHas('appointments', [
            'id' => $response->json('booking.appointment_id'), 'patient_id' => $patient->id,
            'doctor_id' => $doctor->id, 'priority_type' => 'normal',
        ]);
        $this->assertDatabaseCount('appointments', 1);
        $this->assertSame(1, $patient->notifications()->count());
        $this->assertSame(1, $doctor->notifications()->count());
    }

    public function test_matching_department_offers_still_exclude_a_full_routine_day(): void
    {
        $this->blockProvider();
        $doctor = $this->doctor('Cardiology');
        Appointment::factory()->count(18)->sequence(fn (Sequence $sequence): array => ['serial_number' => $sequence->index + 1])
            ->create(['doctor_id' => $doctor->id, 'appointment_date' => today()->toDateString(), 'priority_type' => 'normal']);

        $response = $this->actingAs(User::factory()->patient()->create())->postJson(route('chatbot.message'), ['message' => 'I have palpitations']);

        $response->assertOk();
        $this->assertNotEmpty($response->json('offers'));
        $this->assertNotContains(today()->toDateString(), array_column($response->json('offers'), 'date'));
        $this->assertDatabaseCount('appointments', 18);
    }

    public function test_no_available_doctor_does_not_substitute_an_unrelated_department(): void
    {
        $this->blockProvider();
        Department::create(['name' => 'Cardiology']);
        $this->doctor('Orthopedics');

        $response = $this->actingAs(User::factory()->patient()->create())->postJson(route('chatbot.message'), ['message' => 'I have palpitations']);

        $response->assertOk()->assertJsonPath('offers', []);
        $this->assertStringContainsString('Cardiology department', $response->json('reply'));
        $this->assertStringContainsString('No routine visit choices are available for this department', $response->json('reply'));
        $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $response->json('reply'));
    }

    #[TestWith(['I have headaches and joint pain'])]
    #[TestWith(['I have a skin rash and itching'])]
    #[TestWith(['I feel unwell'])]
    #[TestWith(['I have a fever and cough. Which department should I visit?'])]
    public function test_unclear_or_unmapped_symptoms_ask_one_question_without_inventing_a_department(string $message): void
    {
        $this->blockProvider();
        $this->doctor('Cardiology');

        $response = $this->postJson(route('chatbot.message'), ['message' => $message]);

        $response->assertOk()->assertJsonPath('offers', [])->assertJsonPath('booking', null);
        $reply = $response->json('reply');
        $this->assertStringContainsString('Which symptom is bothering you most?', $reply);
        $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $reply);
        $this->assertStringNotContainsString('General Medicine', $reply);
        $this->assertStringNotContainsString('Dermatology', $reply);
        $this->assertSame(1, substr_count($reply, '?'));
    }

    public function test_a_missing_seeded_department_is_not_advertised_as_available(): void
    {
        $this->blockProvider();

        $response = $this->postJson(route('chatbot.message'), ['message' => 'I have palpitations']);

        $response->assertOk()->assertJsonPath('offers', []);
        $this->assertStringContainsString('Which symptom is bothering you most?', $response->json('reply'));
        $this->assertStringNotContainsString('Cardiology department', $response->json('reply'));
        $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $response->json('reply'));
    }

    public function test_bangla_triage_stays_local_and_keeps_the_exact_safety_disclaimer(): void
    {
        $this->blockProvider();
        $this->doctor('Neurology');

        $response = $this->postJson(route('chatbot.message'), ['message' => 'আমার মাথাব্যথা', 'locale' => 'bn']);

        $response->assertOk()->assertJsonPath('locale', 'bn')->assertJsonPath('offers', []);
        $this->assertStringContainsString('Neurology', $response->json('reply'));
        $this->assertStringContainsString('মূল্যায়নের', $response->json('reply'));
        $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $response->json('reply'));
        Http::assertNothingSent();
    }

    #[TestWith(['patient'])]
    #[TestWith(['doctor'])]
    public function test_only_patient_accounts_receive_triage_booking_offers(string $role): void
    {
        $this->blockProvider();
        $this->doctor('Cardiology');
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->postJson(route('chatbot.message'), ['message' => 'I have palpitations']);

        $response->assertOk();
        $this->assertSame($role === 'patient', $response->json('offers') !== []);
        $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $response->json('reply'));
        $this->assertDatabaseCount('appointments', 0);
    }

    #[TestWith(['I have chest pain. What disease do I have?', 'This may be an emergency'])]
    #[TestWith(['My 2 year old has a fever. Diagnose me.', 'This needs a clinician'])]
    #[TestWith(['What dose should I take for fever? Diagnose me.', 'cannot give a dose'])]
    #[TestWith(['Give me antibiotics for a cough. Diagnose me.', 'cannot recommend an antibiotic'])]
    public function test_existing_safety_guidance_remains_first_and_includes_the_disclaimer(string $message, string $expected): void
    {
        $this->blockProvider();

        $response = $this->postJson(route('chatbot.message'), ['message' => $message]);

        $response->assertOk()->assertJsonPath('booking', null)->assertJsonPath('offers', []);
        $this->assertStringContainsString($expected, $response->json('reply'));
        $this->assertStringContainsString('I cannot diagnose a disease', $response->json('reply'));
        $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $response->json('reply'));
        $this->assertDatabaseCount('appointments', 0);
    }

    #[TestWith(['What disease do I have?'])]
    #[TestWith(['I have headaches. Give me the probability of a condition.'])]
    #[TestWith(['Give a percentage of having Secret-Disease-815.'])]
    #[TestWith(['আমার কী রোগ হয়েছে?'])]
    public function test_direct_diagnosis_or_probability_requests_are_refused_without_ai_or_booking(string $message): void
    {
        $this->blockProvider();

        $response = $this->postJson(route('chatbot.message'), ['message' => $message]);

        $response->assertOk()->assertJsonPath('offers', [])->assertJsonPath('booking', null)->assertJsonPath('sources', []);
        $reply = $response->json('reply');
        $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $reply);
        $this->assertStringNotContainsString('Secret-Disease-815', $reply);
        $this->assertStringNotContainsString('%', $reply);
        $this->assertDatabaseCount('appointments', 0);
    }

    #[DataProvider('modelPredictions')]
    public function test_unsolicited_model_disease_predictions_are_withheld(string $prediction): void
    {
        $this->app->instance(AiCompletionProvider::class, new class($prediction) implements AiCompletionProvider
        {
            public function __construct(private string $prediction) {}

            public function complete(string $systemPrompt, string $userMessage, string $sessionToken): AiCompletion
            {
                return new AiCompletion($this->prediction, 'ok');
            }
        });

        $response = $this->postJson(route('chatbot.message'), ['message' => 'Which doctors are available?']);

        $response->assertOk()->assertJsonPath('booking', null)->assertJsonPath('sources', []);
        $this->assertStringContainsString('I cannot diagnose a disease', $response->json('reply'));
        $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $response->json('reply'));
        $this->assertStringNotContainsString('Secret-Disease-815', $response->json('reply'));
    }

    public static function modelPredictions(): array
    {
        return [
            ['You may have Secret-Disease-815.'],
            ['There is a 90% chance of Secret-Disease-815.'],
            ['Probability of Secret-Disease-815: 0.9.'],
            ['আপনার সম্ভবত Secret-Disease-815 হয়েছে।'],
        ];
    }

    public function test_explicit_confirmation_is_not_changed_by_an_accompanying_diagnosis_request(): void
    {
        $this->blockProvider();
        $doctor = $this->doctor('Cardiology');
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'Confirm this appointment and diagnose me.',
            'confirm' => ['doctor_id' => $doctor->id, 'appointment_date' => today()->toDateString(), 'confirmed' => true],
        ]);

        $response->assertOk()->assertJsonPath('booking.success', true);
        $this->assertStringContainsString('I cannot diagnose a disease', $response->json('reply'));
        $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $response->json('reply'));
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_reviewed_excerpt_fallback_cannot_publish_a_disease_probability(): void
    {
        MedicalKnowledgeArticle::create([
            'title' => 'Fever reference',
            'topic' => 'fever',
            'language' => 'en',
            'content' => 'There is a 75% chance of Secret-Disease-815.',
            'source_url' => 'https://www.nhs.uk/symptoms/fever-in-adults/',
            'source_name' => 'NHS',
            'review_date' => today()->toDateString(),
            'reviewer_name' => 'Dr. Test Reviewer',
            'approval_status' => 'approved',
            'version' => 1,
            'is_active' => true,
        ]);
        $this->app->instance(AiCompletionProvider::class, new class implements AiCompletionProvider
        {
            public function complete(string $systemPrompt, string $userMessage, string $sessionToken): AiCompletion
            {
                return new AiCompletion(null, 'unconfigured');
            }
        });

        $response = $this->postJson(route('chatbot.message'), ['message' => 'I have a fever']);

        $response->assertOk()->assertJsonPath('sources', [])->assertJsonPath('booking', null);
        $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $response->json('reply'));
        $this->assertStringNotContainsString('Secret-Disease-815', $response->json('reply'));
    }

    private function doctor(string $department, string $name = 'Department Doctor'): User
    {
        $doctor = User::factory()->doctor()->create(['name' => $name]);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => Department::firstOrCreate(['name' => $department])->id,
            'qualification' => 'MBBS',
            'designation' => 'Consultant',
        ]);

        return $doctor;
    }

    private function blockProvider(): void
    {
        $this->mock(AiCompletionProvider::class)->shouldNotReceive('complete');
    }
}
