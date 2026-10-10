<?php

namespace App\Events;

use App\Models\LiveQueue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Payload carried on the broadcast — no patient data included.
     *
     * @var array{doctor_id: int, current_serial: int, doctor_present: bool, doctor_name: string, session_status: string, delay_minutes: int, updated_at: string}
     */
    public array $payload;

    /**
     * Create a new event instance.
     */
    public function __construct(public LiveQueue $liveQueue)
    {
        $this->payload = [
            'doctor_id' => $liveQueue->doctor_id,
            'current_serial' => $liveQueue->current_running_serial,
            'doctor_present' => (bool) $liveQueue->doctor_present_status,
            'doctor_name' => $liveQueue->doctor?->name ?? 'Doctor',
            'session_status' => (string) ($liveQueue->session_status ?? 'not_started'),
            'delay_minutes' => (int) ($liveQueue->delay_minutes ?? 0),
            'updated_at' => $liveQueue->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    /**
     * Public channel — patients don't need auth to listen for queue updates.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('queue.doctor.'.$this->liveQueue->doctor_id),
        ];
    }

    /**
     * Broadcast event name for the JS client.
     */
    public function broadcastAs(): string
    {
        return 'queue.updated';
    }

    /**
     * Data to broadcast — only the prepared payload (no Eloquent model).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
