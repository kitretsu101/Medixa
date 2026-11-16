<?php

namespace Tests\Feature;

use App\Models\EmergencyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class EmergencyRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_form_from_the_homepage_and_view_confirmation(): void
    {
        $this->get(route('home'))->assertOk()->assertSee(route('emergency.create'), false);
        $this->get(route('emergency.create'))->assertOk()->assertViewIs('public.emergency')
            ->assertSee('Contact phone')->assertSee('Location')->assertSee('Issue type');
        $this->get(route('emergency.sent'))->assertOk()->assertSee('Your request has been sent');

        $this->assertDatabaseCount('emergency_requests', 0);
    }

    public function test_guest_submission_creates_a_pending_request_and_notifies_only_every_receptionist(): void
    {
        $receptionists = User::factory()->receptionist()->count(2)->create();
        $others = collect([
            User::factory()->patient()->create(),
            User::factory()->doctor()->create(),
            User::factory()->admin()->create(),
            User::factory()->create(['role' => 'lab_assistant']),
        ]);

        $this->post(route('emergency.store'), [
            'patient_name' => 'Test Patient',
            'contact_phone' => '01712345678',
            'location' => 'North gate',
            'issue_type' => 'accident',
            'status' => 'resolved',
            'handled_by' => $others->first()->id,
            'note' => 'This public field must be ignored.',
        ])->assertRedirect(route('emergency.sent'));

        $this->assertDatabaseCount('emergency_requests', 1);
        $this->assertDatabaseHas('emergency_requests', [
            'patient_name' => 'Test Patient',
            'contact_phone' => '01712345678',
            'location' => 'North gate',
            'issue_type' => 'accident',
            'status' => 'pending',
            'handled_by' => null,
            'note' => null,
        ]);
        $this->assertDatabaseCount('notifications', 2);
        foreach ($receptionists as $receptionist) {
            $notification = $receptionist->notifications()->sole();
            $this->assertSame('New emergency request', $notification->data['title']);
            $this->assertSame(route('receptionist.emergency-requests.index'), $notification->data['url']);
        }
        foreach ($others as $other) {
            $this->assertSame(0, $other->notifications()->count());
        }
        $this->get(route('emergency.sent'))->assertDontSee('Test Patient')->assertDontSee('01712345678')->assertDontSee('North gate');
    }

    #[TestWith(['accident'])]
    #[TestWith(['cardiac'])]
    #[TestWith(['breathing'])]
    #[TestWith(['other'])]
    public function test_each_issue_type_can_be_submitted_without_a_patient_name(string $issueType): void
    {
        $this->post(route('emergency.store'), [
            'contact_phone' => '01712345678',
            'location' => 'South gate',
            'issue_type' => $issueType,
        ])->assertRedirect(route('emergency.sent'));

        $this->assertDatabaseHas('emergency_requests', ['patient_name' => null, 'issue_type' => $issueType, 'status' => 'pending']);
    }

    #[TestWith(['contact_phone', 'The contact phone field is required.'])]
    #[TestWith(['location', 'The location field is required.'])]
    public function test_missing_contact_phone_or_location_returns_a_validation_error(string $field, string $message): void
    {
        $payload = ['contact_phone' => '01712345678', 'location' => 'North gate', 'issue_type' => 'other'];
        unset($payload[$field]);

        $this->postJson(route('emergency.store'), $payload)->assertUnprocessable()
            ->assertJsonValidationErrors($field)->assertJsonPath('errors.'.$field.'.0', $message);

        $this->assertDatabaseCount('emergency_requests', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    #[DataProvider('invalidPublicFields')]
    public function test_invalid_public_fields_are_rejected_without_creating_a_request(string $field, mixed $value): void
    {
        $payload = ['contact_phone' => '01712345678', 'location' => 'North gate', 'issue_type' => 'other'];
        $payload[$field] = $value;

        $this->postJson(route('emergency.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('emergency_requests', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public static function invalidPublicFields(): array
    {
        return [
            'unsupported issue' => ['issue_type', 'unknown'],
            'missing issue' => ['issue_type', null],
            'array issue' => ['issue_type', ['accident']],
            'phone is whitespace' => ['contact_phone', '   '],
            'phone is an array' => ['contact_phone', ['01712345678']],
            'phone too long' => ['contact_phone', str_repeat('1', 31)],
            'location too long' => ['location', str_repeat('a', 256)],
            'patient name too long' => ['patient_name', str_repeat('a', 256)],
        ];
    }

    #[TestWith(['patient'])]
    #[TestWith(['doctor'])]
    #[TestWith(['admin'])]
    #[TestWith(['lab_assistant'])]
    public function test_non_receptionists_receive_403_on_both_receptionist_routes(string $role): void
    {
        $actor = User::factory()->create(['role' => $role]);
        $emergencyRequest = EmergencyRequest::factory()->create();

        $this->actingAs($actor)->get(route('receptionist.emergency-requests.index'))->assertForbidden();
        $this->post(route('receptionist.emergency-requests.update-status', $emergencyRequest), [
            'status' => 'resolved', 'note' => 'Unauthorised change.',
        ])->assertForbidden();

        $this->assertDatabaseHas('emergency_requests', ['id' => $emergencyRequest->id, 'status' => 'pending', 'handled_by' => null, 'note' => null]);
    }

    public function test_guest_cannot_list_or_update_emergency_requests(): void
    {
        $emergencyRequest = EmergencyRequest::factory()->create();

        $this->get(route('receptionist.emergency-requests.index'))->assertRedirect(route('login'));
        $this->post(route('receptionist.emergency-requests.update-status', $emergencyRequest), [
            'status' => 'resolved',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('emergency_requests', ['id' => $emergencyRequest->id, 'status' => 'pending']);
    }

    #[TestWith(['pending'])]
    #[TestWith(['acknowledged'])]
    #[TestWith(['resolved'])]
    public function test_receptionist_can_save_each_status_and_note_with_the_correct_handler(string $status): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $emergencyRequest = EmergencyRequest::factory()->create();

        $this->actingAs($receptionist)->post(route('receptionist.emergency-requests.update-status', $emergencyRequest), [
            'status' => $status,
            'note' => 'Contacted the caller and arranged manual follow-up.',
            'handled_by' => 999999,
            'contact_phone' => 'forged phone',
        ])->assertRedirect(route('receptionist.emergency-requests.index'));

        $this->assertDatabaseHas('emergency_requests', [
            'id' => $emergencyRequest->id,
            'status' => $status,
            'note' => 'Contacted the caller and arranged manual follow-up.',
            'handled_by' => $receptionist->id,
            'contact_phone' => '01712345678',
        ]);
        $this->assertTrue($emergencyRequest->fresh()->handledBy->is($receptionist));
    }

    public function test_status_can_be_updated_without_a_note(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $emergencyRequest = EmergencyRequest::factory()->create();

        $this->actingAs($receptionist)->post(route('receptionist.emergency-requests.update-status', $emergencyRequest), [
            'status' => 'acknowledged',
        ])->assertRedirect(route('receptionist.emergency-requests.index'));

        $this->assertDatabaseHas('emergency_requests', ['id' => $emergencyRequest->id, 'note' => null, 'handled_by' => $receptionist->id]);
    }

    public function test_invalid_status_and_note_are_rejected_without_changing_the_request(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $emergencyRequest = EmergencyRequest::factory()->create();

        $this->actingAs($receptionist)->postJson(route('receptionist.emergency-requests.update-status', $emergencyRequest), [
            'status' => 'dispatched', 'note' => str_repeat('a', 2001),
        ])->assertUnprocessable()->assertJsonValidationErrors(['status', 'note']);

        $this->assertDatabaseHas('emergency_requests', ['id' => $emergencyRequest->id, 'status' => 'pending', 'handled_by' => null, 'note' => null]);
    }

    public function test_pending_requests_are_highlighted_first_and_each_group_is_newest_first(): void
    {
        $this->freezeSecond();
        $receptionist = User::factory()->receptionist()->create();
        $oldPending = EmergencyRequest::factory()->create(['created_at' => now()->subHours(3)]);
        $newPending = EmergencyRequest::factory()->create(['created_at' => now()->subHours(2)]);
        $acknowledged = EmergencyRequest::factory()->create(['status' => 'acknowledged', 'created_at' => now()->subHour()]);
        $resolved = EmergencyRequest::factory()->create(['status' => 'resolved']);

        $this->actingAs($receptionist)->get(route('receptionist.emergency-requests.index'))->assertOk()
            ->assertViewHas('emergencyRequests', fn ($rows): bool => $rows->modelKeys() === [$newPending->id, $oldPending->id, $resolved->id, $acknowledged->id])
            ->assertSee('border-red-300 bg-red-50', false);
    }

    public function test_receptionist_list_escapes_public_details_and_saved_notes(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $emergencyRequest = EmergencyRequest::factory()->create([
            'patient_name' => '<script>name()</script>',
            'location' => '<script>location()</script>',
            'note' => '</textarea><script>note()</script>',
        ]);

        $this->actingAs($receptionist)->get(route('receptionist.emergency-requests.index'))->assertOk()
            ->assertSee($emergencyRequest->patient_name)->assertDontSee($emergencyRequest->patient_name, false)
            ->assertSee($emergencyRequest->location)->assertDontSee($emergencyRequest->location, false)
            ->assertSee($emergencyRequest->note)->assertDontSee($emergencyRequest->note, false);
    }

    #[TestWith(['emergency.store'])]
    #[TestWith(['patient.assistance-requests.store'])]
    public function test_emergency_and_assistance_posts_both_allow_ten_attempts_per_minute(string $routeName): void
    {
        $this->freezeSecond();
        if ($routeName === 'patient.assistance-requests.store') {
            $this->actingAs(User::factory()->patient()->create());
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson(route($routeName), [])->assertUnprocessable()->assertHeader('X-RateLimit-Limit', '10');
        }
        $this->postJson(route($routeName), [])->assertTooManyRequests()->assertHeader('Retry-After', '60');
        $this->travel(61)->seconds();
        $this->postJson(route($routeName), [])->assertUnprocessable();

        $this->assertDatabaseCount('emergency_requests', 0);
        $this->assertDatabaseCount('notifications', 0);
    }
}
