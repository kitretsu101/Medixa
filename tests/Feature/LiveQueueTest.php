<?php

namespace Tests\Feature;

use App\Events\QueueUpdated;
use App\Models\LiveQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LiveQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_increment_live_queue_serial_and_broadcasts_event(): void
    {
        Event::fake([QueueUpdated::class]);

        $doctor = User::factory()->doctor()->create();

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
                && $event->broadcastOn()[0]->name === 'queue.doctor.' . $doctor->id
                && $event->broadcastAs() === 'queue.updated';
        });
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
