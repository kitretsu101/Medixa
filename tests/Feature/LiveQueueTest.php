<?php

namespace Tests\Feature;

use App\Events\QueueUpdated;
use App\Models\Appointment;
use App\Models\LiveQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LiveQueueTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([0])]
    #[TestWith([5])]
    public function test_increment_without_appointments_today_is_no_op(int $currentSerial): void
    {
        $this->freezeTime();
        Event::fake([QueueUpdated::class]);
        $doctor = User::factory()->doctor()->create();
        $queue = LiveQueue::create([
            'doctor_id' => $doctor->id,
            'current_running_serial' => $currentSerial,
            'doctor_present_status' => false,
            'session_status' => 'paused',
            'delay_minutes' => 7,
        ]);
        Appointment::factory()->create(['doctor_id' => $doctor->id, 'appointment_date' => today()->subDay(), 'serial_number' => 11]);
        Appointment::factory()->create(['doctor_id' => $doctor->id, 'appointment_date' => today()->addDay(), 'serial_number' => 12]);
        Appointment::factory()->create(['appointment_date' => today(), 'serial_number' => 13]);
        $original = $queue->fresh()->getRawOriginal();
        $this->travel(1)->seconds();

        $this->actingAs($doctor)->postJson(route('doctor.queue.increment'))->assertOk()
            ->assertExactJson([
                'message' => 'No more patients waiting',
                'current_serial' => $currentSerial,
                'doctor_present' => false,
                'session_status' => 'paused',
                'delay_minutes' => 7,
            ]);

        $this->assertSame($original, $queue->fresh()->getRawOriginal());
        $this->assertDatabaseCount('queue_events', 0);
        $this->assertDatabaseCount('notifications', 0);
        Event::assertNotDispatched(QueueUpdated::class);
    }

    public function test_first_increment_without_appointments_stays_at_zero(): void
    {
        Event::fake([QueueUpdated::class]);
        $doctor = User::factory()->doctor()->create();

        $this->actingAs($doctor)->postJson(route('doctor.queue.increment'))->assertOk()
            ->assertJson(['message' => 'No more patients waiting', 'current_serial' => 0]);

        $this->assertDatabaseHas('live_queues', ['doctor_id' => $doctor->id, 'current_running_serial' => 0]);
        $this->assertDatabaseCount('queue_events', 0);
        $this->assertDatabaseCount('notifications', 0);
        Event::assertNotDispatched(QueueUpdated::class);
    }

    public function test_doctor_can_increment_live_queue_serial_and_broadcasts_event(): void
    {
        Event::fake([QueueUpdated::class]);

        $doctor = User::factory()->doctor()->create();
        Appointment::factory()->count(3)->sequence(
            ['serial_number' => 1],
            ['serial_number' => 2],
            ['serial_number' => 3],
        )->create(['doctor_id' => $doctor->id, 'appointment_date' => today()]);

        $response = $this->actingAs($doctor)->postJson(route('doctor.queue.increment'));

        $response->assertOk()
            ->assertJson([
                'message' => 'Serial incremented.',
                'current_serial' => 1,
            ]);

        $this->assertDatabaseHas('live_queues', [
            'doctor_id' => $doctor->id,
            'current_running_serial' => 1,
        ]);

        Event::assertDispatched(QueueUpdated::class, function (QueueUpdated $event) use ($doctor) {
            return $event->liveQueue->doctor_id === $doctor->id
                && $event->payload['current_serial'] === 1
                && $event->broadcastOn()[0]->name === 'queue.doctor.'.$doctor->id
                && $event->broadcastAs() === 'queue.updated';
        });
    }

    public function test_increment_reaches_highest_serial_then_stops_without_repeated_side_effects(): void
    {
        $this->freezeTime();
        Event::fake([QueueUpdated::class]);
        $doctor = User::factory()->doctor()->create();
        Appointment::factory()->create(['doctor_id' => $doctor->id, 'appointment_date' => today(), 'serial_number' => 3]);
        $queue = LiveQueue::create([
            'doctor_id' => $doctor->id,
            'current_running_serial' => 2,
            'doctor_present_status' => true,
        ]);

        $this->actingAs($doctor)->postJson(route('doctor.queue.increment'))->assertOk()
            ->assertJson(['message' => 'Serial incremented.', 'current_serial' => 3]);
        $original = $queue->fresh()->getRawOriginal();
        $this->travel(1)->seconds();
        $this->postJson(route('doctor.queue.increment'))->assertOk()
            ->assertJson(['message' => 'No more patients waiting', 'current_serial' => 3]);
        $this->postJson(route('doctor.queue.increment'))->assertOk()
            ->assertJson(['message' => 'No more patients waiting', 'current_serial' => 3]);

        $this->assertSame($original, $queue->fresh()->getRawOriginal());
        $this->assertDatabaseCount('queue_events', 1);
        $this->assertDatabaseCount('notifications', 1);
        Event::assertDispatched(QueueUpdated::class, 1);
    }

    #[TestWith([3])]
    #[TestWith([4])]
    public function test_increment_at_or_above_highest_serial_does_not_call_a_waiting_patient(int $currentSerial): void
    {
        $this->freezeTime();
        Event::fake([QueueUpdated::class]);
        $doctor = User::factory()->doctor()->create();
        $waiting = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'appointment_date' => today(),
            'serial_number' => 1,
            'status' => 'checked_in',
            'checked_in_at' => now()->subMinutes(5),
        ]);
        Appointment::factory()->create(['doctor_id' => $doctor->id, 'appointment_date' => today(), 'serial_number' => 3]);
        $queue = LiveQueue::create([
            'doctor_id' => $doctor->id,
            'current_running_serial' => $currentSerial,
            'doctor_present_status' => true,
            'session_status' => 'paused',
            'delay_minutes' => 9,
        ]);
        $original = $queue->fresh()->getRawOriginal();
        $this->travel(1)->seconds();

        $this->actingAs($doctor)->postJson(route('doctor.queue.increment'))->assertOk()
            ->assertExactJson([
                'message' => 'No more patients waiting',
                'current_serial' => $currentSerial,
                'doctor_present' => true,
                'session_status' => 'paused',
                'delay_minutes' => 9,
            ]);

        $this->assertSame($original, $queue->fresh()->getRawOriginal());
        $this->assertSame('checked_in', $waiting->fresh()->status);
        $this->assertDatabaseCount('queue_events', 0);
        $this->assertDatabaseCount('notifications', 0);
        Event::assertNotDispatched(QueueUpdated::class);
    }

    public function test_doctor_can_decrement_serial_and_floors_at_zero(): void
    {
        Event::fake([QueueUpdated::class]);

        $doctor = User::factory()->doctor()->create();
        LiveQueue::create([
            'doctor_id' => $doctor->id,
            'current_running_serial' => 2,
            'doctor_present_status' => true,
        ]);

        // Decrement 2 -> 1
        $response = $this->actingAs($doctor)->postJson(route('doctor.queue.decrement'));
        $response->assertOk()->assertJson(['current_serial' => 1]);

        // Decrement 1 -> 0
        $this->actingAs($doctor)->postJson(route('doctor.queue.decrement'));

        // Decrement 0 -> remains 0 (no negative serials)
        $response = $this->actingAs($doctor)->postJson(route('doctor.queue.decrement'));
        $response->assertOk()->assertJson(['current_serial' => 0]);

        $this->assertDatabaseHas('live_queues', [
            'doctor_id' => $doctor->id,
            'current_running_serial' => 0,
        ]);

        Event::assertDispatched(QueueUpdated::class);
    }

    public function test_doctor_can_toggle_presence_status(): void
    {
        Event::fake([QueueUpdated::class]);

        $doctor = User::factory()->doctor()->create();
        $queue = LiveQueue::create([
            'doctor_id' => $doctor->id,
            'current_running_serial' => 5,
            'doctor_present_status' => false,
        ]);

        // Away -> Present
        $response = $this->actingAs($doctor)->postJson(route('doctor.queue.toggle-presence'));
        $response->assertOk()->assertJson([
            'doctor_present' => true,
            'message' => 'Marked as present.',
        ]);

        // Present -> Away
        $response = $this->actingAs($doctor)->postJson(route('doctor.queue.toggle-presence'));
        $response->assertOk()->assertJson([
            'doctor_present' => false,
            'message' => 'Marked as away.',
        ]);

        Event::assertDispatched(QueueUpdated::class, 2);
    }

    public function test_non_doctor_cannot_manipulate_queue(): void
    {
        Event::fake([QueueUpdated::class]);

        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->postJson(route('doctor.queue.increment'));
        $response->assertForbidden();

        $response = $this->actingAs($patient)->postJson(route('doctor.queue.decrement'));
        $response->assertForbidden();

        $response = $this->actingAs($patient)->postJson(route('doctor.queue.toggle-presence'));
        $response->assertForbidden();

        Event::assertNotDispatched(QueueUpdated::class);
    }

    public function test_queue_updated_event_payload_contains_no_patient_identifying_data(): void
    {
        $doctor = User::factory()->doctor()->create(['name' => 'Dr. Elizabeth Blackwell']);
        $queue = LiveQueue::create([
            'doctor_id' => $doctor->id,
            'current_running_serial' => 14,
            'doctor_present_status' => true,
        ]);

        $event = new QueueUpdated($queue);

        $payload = $event->broadcastWith();

        $this->assertSame(14, $payload['current_serial']);
        $this->assertTrue($payload['doctor_present']);
        $this->assertSame('Dr. Elizabeth Blackwell', $payload['doctor_name']);

        // Strictly verify payload only contains hospital / doctor queue metadata
        $this->assertArrayNotHasKey('patient_id', $payload);
        $this->assertArrayNotHasKey('patient_name', $payload);
        $this->assertArrayNotHasKey('patient_email', $payload);
        $this->assertArrayNotHasKey('phone', $payload);
        $this->assertArrayNotHasKey('dob', $payload);
    }
}
