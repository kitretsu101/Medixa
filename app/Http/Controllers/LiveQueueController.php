<?php

namespace App\Http\Controllers;

use App\Models\LiveQueue;
use App\Services\NotificationService;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LiveQueueController extends Controller
{
    public function __construct(
        protected QueueService $queueService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Increment the doctor's current running serial and broadcast the change.
     */
    public function increment(Request $request): JsonResponse
    {
        $queue = $this->queueService->increment($this->resolveQueue($request), $request->user()?->id);
        $this->notificationService->notifyCalledPatient($queue);

        return $this->payload('Serial incremented.', $queue);
    }

    /**
     * Decrement the doctor's current running serial (floor at 0) and broadcast.
     */
    public function decrement(Request $request): JsonResponse
    {
        $queue = $this->queueService->decrement($this->resolveQueue($request));

        return $this->payload('Serial decremented.', $queue);
    }

    public function delay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'minutes' => ['required', 'integer', 'between:0,180'],
        ]);

        $queue = $this->queueService->markDelay(
            $this->resolveQueue($request),
            (int) $validated['minutes'],
            $request->user()?->id,
        );

        return $this->payload(
            $queue->delay_minutes > 0 ? 'Delay recorded.' : 'Delay cleared.',
            $queue,
        );
    }

    public function pause(Request $request): JsonResponse
    {
        $queue = $this->resolveQueue($request);
        $paused = $queue->session_status !== 'paused';
        $queue = $this->queueService->setPaused($queue, $paused, $request->user()?->id);

        return $this->payload($paused ? 'Queue paused.' : 'Queue resumed.', $queue);
    }

    /**
     * Toggle the doctor_present_status flag and broadcast.
     */
    public function togglePresence(Request $request): JsonResponse
    {
        $queue = $this->queueService->togglePresence($this->resolveQueue($request), $request->user()?->id);

        return $this->payload(
            $queue->doctor_present_status ? 'Marked as present.' : 'Marked as away.',
            $queue,
        );
    }

    /**
     * Resolve (or create) the LiveQueue row for the authenticated doctor.
     */
    private function resolveQueue(Request $request): LiveQueue
    {
        $doctor = $request->user();

        if (! $doctor?->isDoctor()) {
            abort(Response::HTTP_FORBIDDEN, 'Only doctors can manage live queues.');
        }

        return $this->queueService->queueFor($doctor);
    }

    private function payload(string $message, LiveQueue $queue): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'current_serial' => $queue->current_running_serial,
            'doctor_present' => $queue->doctor_present_status,
            'session_status' => $queue->session_status,
            'delay_minutes' => (int) $queue->delay_minutes,
        ]);
    }
}
