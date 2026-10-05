<?php

namespace App\Http\Controllers;

use App\Events\QueueUpdated;
use App\Models\LiveQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LiveQueueController extends Controller
{
    /**
     * Increment the doctor's current running serial and broadcast the change.
     */
    public function increment(Request $request): JsonResponse
    {
        $queue = $this->resolveQueue($request);

        $queue->update([
            'current_running_serial' => $queue->current_running_serial + 1,
            'updated_at' => now(),
        ]);

        $queue->refresh()->load('doctor');
        QueueUpdated::dispatch($queue);

        return response()->json([
            'message' => 'Serial incremented.',
            'current_serial' => $queue->current_running_serial,
            'doctor_present' => $queue->doctor_present_status,
        ]);
    }

    /**
     * Decrement the doctor's current running serial (floor at 0) and broadcast.
     */
    public function decrement(Request $request): JsonResponse
    {
        $queue = $this->resolveQueue($request);

        $newSerial = max(0, $queue->current_running_serial - 1);

        $queue->update([
            'current_running_serial' => $newSerial,
            'updated_at' => now(),
        ]);

        $queue->refresh()->load('doctor');
        QueueUpdated::dispatch($queue);

        return response()->json([
            'message' => 'Serial decremented.',
            'current_serial' => $queue->current_running_serial,
            'doctor_present' => $queue->doctor_present_status,
        ]);
    }

    /**
     * Toggle the doctor_present_status flag and broadcast.
     */
    public function togglePresence(Request $request): JsonResponse
    {
        $queue = $this->resolveQueue($request);

        $queue->update([
            'doctor_present_status' => ! $queue->doctor_present_status,
            'updated_at' => now(),
        ]);

        $queue->refresh()->load('doctor');
        QueueUpdated::dispatch($queue);

        return response()->json([
            'message' => $queue->doctor_present_status ? 'Marked as present.' : 'Marked as away.',
            'current_serial' => $queue->current_running_serial,
            'doctor_present' => $queue->doctor_present_status,
        ]);
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

        return LiveQueue::firstOrCreate(
            ['doctor_id' => $doctor->id],
            ['current_running_serial' => 0, 'doctor_present_status' => false],
        );
    }
}
