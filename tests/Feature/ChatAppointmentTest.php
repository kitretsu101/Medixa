<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\DoctorProfile;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatAppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_booking_request_offers_open_routine_visits_and_does_not_book(): void
    {
        $doctor = $this->doctor();
        $hidden = User::factory()->patient()->create(['name' => 'Hidden Patient Name 991']);
        Appointment::factory()->create([
            'patient_id' => $hidden->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDays(3)->toDateString(),
            'serial_number' => 4,
            'status' => 'pending',
            'priority_type' => 'normal',
        ]);
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'I want to book an appointment',
        ]);

        $response->assertOk()
            ->assertJsonPath('booking', null);

        $offers = $response->json('offers');
        $this->assertNotEmpty($offers);
        $this->assertSame($doctor->id, $offers[0]['doctor_id']);
        $this->assertSame(18, $offers[0]['slots_remaining']);
        $this->assertStringNotContainsString('Hidden Patient Name 991', $response->getContent());
        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_completed_visit_reduces_the_offered_routine_spaces(): void
    {
        $doctor = $this->doctor();
        $date = now()->addDay()->toDateString();
        Appointment::factory()->completed()->create([
            'patient_id' => User::factory()->patient(),
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'serial_number' => 1,
            'priority_type' => 'normal',
        ]);
        Appointment::factory()->create([
            'patient_id' => User::factory()->patient(),
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'serial_number' => 2,
            'status' => 'cancelled',
            'priority_type' => 'normal',
        ]);

        $response = $this->actingAs(User::factory()->patient()->create())->postJson(route('chatbot.message'), [
            'message' => 'I want to book an appointment',
        ]);

        $offer = collect($response->json('offers'))->firstWhere('date', $date);

        $this->assertNotNull($offer);
        $this->assertSame(17, $offer['slots_remaining']);
    }

    public function test_explicit_confirmation_books_through_the_appointment_service(): void
    {
        $doctor = $this->doctor();
        $patient = User::factory()->patient()->create();
        $date = now()->addDay()->toDateString();

        $response = $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'Confirm this routine appointment',
            'confirm' => [
                'doctor_id' => $doctor->id,
                'appointment_date' => $date,
                'confirmed' => true,
                'priority_type' => 'emergency',
                'serial_number' => 99,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('booking.success', true);

        $this->assertDatabaseHas('appointments', [
            'id' => $response->json('booking.appointment_id'),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'serial_number' => 1,
            'priority_type' => 'normal',
            'status' => 'pending',
        ]);
    }

    public function test_confirmation_without_the_accepted_flag_does_not_book(): void
    {
        $doctor = $this->doctor();
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'Confirm this routine appointment',
            'confirm' => [
                'doctor_id' => $doctor->id,
                'appointment_date' => now()->addDay()->toDateString(),
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['confirm.confirmed']);

        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_a_second_confirmation_for_the_same_visit_is_rejected(): void
    {
        $doctor = $this->doctor();
        $patient = User::factory()->patient()->create();
        $date = now()->addDay()->toDateString();
        $payload = [
            'message' => 'Confirm this routine appointment',
            'confirm' => [
                'doctor_id' => $doctor->id,
                'appointment_date' => $date,
                'confirmed' => true,
            ],
        ];

        $this->actingAs($patient)->postJson(route('chatbot.message'), $payload)
            ->assertOk()
            ->assertJsonPath('booking.success', true);

        $second = $this->actingAs($patient)->postJson(route('chatbot.message'), $payload);

        $second->assertOk()
            ->assertJsonPath('booking.success', false);

        $this->assertStringContainsString('already have an active appointment', (string) $second->json('booking.message'));
        $this->assertSame(1, Appointment::query()->where('patient_id', $patient->id)->count());
    }

    public function test_confirmation_fails_when_the_routine_day_fills_up(): void
    {
        $doctor = $this->doctor();
        $patient = User::factory()->patient()->create();
        $date = now()->addDay()->toDateString();
        $service = $this->app->make(AppointmentService::class);

        for ($index = 0; $index < AppointmentService::DAILY_CAP - AppointmentService::EMERGENCY_BUFFER; $index++) {
            $service->book(User::factory()->patient()->create(), $doctor, $date);
        }

        $response = $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'Confirm this routine appointment',
            'confirm' => [
                'doctor_id' => $doctor->id,
                'appointment_date' => $date,
                'confirmed' => true,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('booking.success', false);

        $this->assertStringContainsString('Routine slots are full', (string) $response->json('booking.message'));
        $this->assertSame(0, Appointment::query()->where('patient_id', $patient->id)->count());
    }

    public function test_a_full_date_is_left_out_of_the_offered_visits(): void
    {
        $doctor = $this->doctor();
        $date = now()->addDays(2)->toDateString();
        $service = $this->app->make(AppointmentService::class);

        for ($index = 0; $index < AppointmentService::DAILY_CAP - AppointmentService::EMERGENCY_BUFFER; $index++) {
            $service->book(User::factory()->patient()->create(), $doctor, $date);
        }

        $response = $this->actingAs(User::factory()->patient()->create())->postJson(route('chatbot.message'), [
            'message' => 'I want to book an appointment',
        ]);

        $dates = collect($response->json('offers'))->pluck('date');

        $this->assertFalse($dates->contains($date));
        $this->assertTrue($dates->isNotEmpty());
    }

    public function test_emergency_confirmation_does_not_book_a_routine_visit(): void
    {
        $doctor = $this->doctor();
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'I have chest pain. Book an appointment.',
            'confirm' => [
                'doctor_id' => $doctor->id,
                'appointment_date' => now()->addDay()->toDateString(),
                'confirmed' => true,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('booking', null)
            ->assertJsonPath('offers', []);

        $this->assertStringContainsString('This may be an emergency', (string) $response->json('reply'));
        $this->assertStringContainsString('do not wait for a routine appointment', (string) $response->json('reply'));
        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_a_signed_in_doctor_cannot_book_through_the_chatbot(): void
    {
        $doctor = $this->doctor();

        $this->actingAs($doctor)->postJson(route('chatbot.message'), [
            'message' => 'Confirm this routine appointment',
            'confirm' => [
                'doctor_id' => $doctor->id,
                'appointment_date' => now()->addDay()->toDateString(),
                'confirmed' => true,
            ],
        ])->assertOk()
            ->assertJsonPath('booking.success', false)
            ->assertJsonPath('booking.message', 'Please log in to your patient account to book an appointment.');

        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_high_risk_reply_offers_a_routine_visit_without_calling_it_emergency_care(): void
    {
        $this->doctor();

        $response = $this->actingAs(User::factory()->patient()->create())->postJson(route('chatbot.message'), [
            'message' => 'My 2 year old has a fever',
        ]);

        $response->assertOk()
            ->assertJsonPath('booking', null);

        $this->assertStringContainsString('This needs a clinician', (string) $response->json('reply'));
        $this->assertStringNotContainsString('This may be an emergency', (string) $response->json('reply'));
        $this->assertNotEmpty($response->json('offers'));
        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_confirmation_rejects_a_doctor_who_is_not_in_the_directory(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'Confirm this routine appointment',
            'confirm' => [
                'doctor_id' => 999999,
                'appointment_date' => now()->addDay()->toDateString(),
                'confirmed' => true,
            ],
        ])->assertOk()
            ->assertJsonPath('booking.success', false)
            ->assertJsonPath('booking.message', 'That doctor is not open for booking.');

        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_a_past_date_confirmation_is_rejected(): void
    {
        $doctor = $this->doctor();
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'Confirm this routine appointment',
            'confirm' => [
                'doctor_id' => $doctor->id,
                'appointment_date' => now()->subDay()->toDateString(),
                'confirmed' => true,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('booking.success', false);

        $this->assertStringContainsString('cannot be booked in the past', (string) $response->json('booking.message'));
        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_an_invalid_confirmation_date_is_rejected(): void
    {
        $doctor = $this->doctor();
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'Confirm this routine appointment',
            'confirm' => [
                'doctor_id' => $doctor->id,
                'appointment_date' => 'tomorrow',
                'confirmed' => true,
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['confirm.appointment_date']);

        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_a_guest_booking_request_does_not_reveal_the_schedule(): void
    {
        $doctor = $this->doctor();
        $hidden = User::factory()->patient()->create(['name' => 'Hidden Patient Name 991']);
        Appointment::factory()->create([
            'patient_id' => $hidden->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'serial_number' => 7,
            'status' => 'pending',
        ]);

        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'I want to book an appointment',
        ]);

        $response->assertOk()
            ->assertJsonPath('booking', null)
            ->assertJsonPath('offers', [])
            ->assertJsonPath('reply', 'Please log in to your patient account to book an appointment.');

        $this->assertStringNotContainsString('Hidden Patient Name 991', $response->getContent());
        $this->assertSame(1, Appointment::query()->count());
    }

    private function doctor(): User
    {
        $doctor = User::factory()->doctor()->create(['name' => 'Dr. Robert Liston']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'department_id' => Department::firstOrCreate(['name' => 'General Surgery'])->id,
            'qualification' => 'MBBS',
            'designation' => 'Consultant',
        ]);

        return $doctor;
    }
}
