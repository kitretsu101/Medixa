<?php

namespace App\Services;

use App\Events\QueueUpdated;
use App\Models\Appointment;
use App\Models\LiveQueue;
use App\Models\QueueEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueueService
{
    public function __construct(
        protected QueueOptimizationService $optimizer,
        protected NotificationService $notifications,
    ) {}

    /**
     * Resolve the single live queue row for a doctor.
     */
    public function queueFor(User $doctor): LiveQueue
    {
        return LiveQueue::firstOrCreate(
            ['doctor_id' => $doctor->id],
            ['current_running_serial' => 0, 'doctor_present_status' => false],
        );
    }

    public function increment(LiveQueue $queue, ?int $actorId = null): LiveQueue
    {
        return DB::transaction(function () use ($queue, $actorId): LiveQueue {
            $locked = LiveQueue::query()->whereKey($queue->id)->lockForUpdate()->firstOrFail();
            $next = $this->optimizer->nextWaiting((int) $locked->doctor_id);

            if ($next !== null) {
                return $this->callNext($locked, $next, $actorId);
            }

            $locked->update([
                'current_running_serial' => $locked->current_running_serial + 1,
                'session_status' => 'running',
                'updated_at' => now(),
            ]);

            $this->record($locked->doctor_id, null, $actorId, 'serial_advanced', 'No checked-in patient was waiting, so the serial advanced by one.');

            return $this->broadcast($locked);
        });
    }

    public function decrement(LiveQueue $queue): LiveQueue
    {
        $queue->update([
            'current_running_serial' => max(0, $queue->current_running_serial - 1),
            'updated_at' => now(),
        ]);

        return $this->broadcast($queue);
    }

    public function togglePresence(LiveQueue $queue, ?int $actorId = null): LiveQueue
    {
        $present = ! $queue->doctor_present_status;

        $queue->update([
            'doctor_present_status' => $present,
            'session_status' => $present ? 'present' : 'absent',
            'updated_at' => now(),
        ]);

        $this->record(
            $queue->doctor_id,
            null,
            $actorId,
            $present ? 'doctor_present' : 'doctor_absent',
            $present ? 'Doctor marked present.' : 'Doctor marked away.',
        );

        return $this->broadcast($queue);
    }

    public function markDelay(LiveQueue $queue, int $minutes, ?int $actorId = null): LiveQueue
    {
        $queue->update([
            'delay_minutes' => $minutes,
            'session_status' => $minutes > 0 ? 'delayed' : ($queue->doctor_present_status ? 'present' : 'not_started'),
            'updated_at' => now(),
        ]);

        $this->record(
            $queue->doctor_id,
            null,
            $actorId,
            $minutes > 0 ? 'doctor_delayed' : 'delay_cleared',
            $minutes > 0
                ? 'Doctor delayed by approximately '.$minutes.' minutes.'
                : 'Doctor delay cleared.',
        );

        if ($minutes > 0) {
            $this->notifyWaiting($queue, 'Doctor delayed', 'Doctor is currently delayed by approximately '.$minutes.' minutes.', 'warning');
        }

        return $this->broadcast($queue);
    }

    public function setPaused(LiveQueue $queue, bool $paused, ?int $actorId = null): LiveQueue
    {
        $queue->update([
            'session_status' => $paused ? 'paused' : ($queue->doctor_present_status ? 'running' : 'not_started'),
            'updated_at' => now(),
        ]);

        $this->record(
            $queue->doctor_id,
            null,
            $actorId,
            $paused ? 'queue_paused' : 'queue_resumed',
            $paused ? 'Doctor paused the queue.' : 'Doctor resumed the queue.',
        );

        if ($paused) {
            $this->notifyWaiting($queue, 'Queue paused', 'The doctor has paused the queue. Estimated times will update when it resumes.', 'warning');
        }

        return $this->broadcast($queue);
    }

    public function checkIn(Appointment $appointment, User $actor): Appointment
    {
        if ((int) $appointment->patient_id !== (int) $actor->id && ! $actor->isReceptionist() && ! $actor->isAdmin()) {
            abort(403);
        }

        if (! in_array($appointment->status, ['pending', 'confirmed'], true)) {
            throw ValidationException::withMessages([
                'appointment' => 'This appointment cannot be checked in.',
            ]);
        }

        if (! $appointment->appointment_date?->isToday()) {
            throw ValidationException::withMessages([
                'appointment' => 'Check-in is available on the appointment day.',
            ]);
        }

        return DB::transaction(function () use ($appointment, $actor): Appointment {
            $appointment->update([
                'status' => 'checked_in',
                'checked_in_at' => now(),
            ]);

            $this->record(
                (int) $appointment->doctor_id,
                $appointment->id,
                $actor->id,
                'checked_in',
                'Patient checked in and joined the queue.',
            );

            $doctor = User::query()->find($appointment->doctor_id);

            if ($doctor !== null) {
                $this->broadcast($this->queueFor($doctor));
            }

            return $appointment->refresh();
        });
    }

    public function markNoShow(Appointment $appointment, User $actor): Appointment
    {
        if (! in_array($appointment->status, ['pending', 'confirmed'], true)) {
            throw ValidationException::withMessages([
                'appointment' => 'Only an appointment that has not been checked in can be marked as a no-show.',
            ]);
        }

        $appointment->update(['status' => 'no_show']);

        $this->record(
            (int) $appointment->doctor_id,
            $appointment->id,
            $actor->id,
            'no_show',
            'Appointment marked as a no-show.',
        );

        return $appointment;
    }

    private function callNext(LiveQueue $queue, Appointment $next, ?int $actorId): LiveQueue
    {
        Appointment::query()
            ->where('doctor_id', $queue->doctor_id)
            ->whereDate('appointment_date', today())
            ->where('status', 'in_progress')
            ->whereKeyNot($next->id)
            ->update([
                'status' => 'completed',
                'consultation_ended_at' => now(),
            ]);

        $next->update([
            'status' => 'in_progress',
            'consultation_started_at' => $next->consultation_started_at ?? now(),
        ]);

        $queue->update([
            'current_running_serial' => $next->serial_number,
            'session_status' => 'running',
            'doctor_present_status' => true,
            'updated_at' => now(),
        ]);

        $this->record(
            (int) $queue->doctor_id,
            $next->id,
            $actorId,
            'patient_called',
            $this->optimizer->explain($next->refresh()),
        );

        $this->notifyMilestones((int) $queue->doctor_id);

        return $this->broadcast($queue);
    }

    private function notifyMilestones(int $doctorId): void
    {
        $queue = LiveQueue::query()->where('doctor_id', $doctorId)->first();
        $waiting = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', today())
            ->whereIn('status', QueueOptimizationService::WAITING_STATUSES)
            ->with('patient')
            ->get();

        foreach ($waiting as $appointment) {
            $ahead = $this->optimizer->patientsAhead($appointment, $queue);

            if (! in_array($ahead, QueueOptimizationService::MILESTONES, true)) {
                continue;
            }

            if ((int) $appointment->last_queue_notice === $ahead) {
                continue;
            }

            $appointment->update(['last_queue_notice' => $ahead]);

            if ($appointment->patient === null) {
                continue;
            }

            $this->notifications->alert(
                $appointment->patient,
                'Queue update',
                $ahead === 1
                    ? 'You are next. Please stay near the chamber.'
                    : 'There are '.$ahead.' patients ahead of you.',
                route('patient.dashboard'),
                'info',
            );
        }
    }

    private function notifyWaiting(LiveQueue $queue, string $title, string $body, string $level): void
    {
        $patients = Appointment::query()
            ->where('doctor_id', $queue->doctor_id)
            ->whereDate('appointment_date', today())
            ->whereIn('status', QueueOptimizationService::WAITING_STATUSES)
            ->with('patient')
            ->get();

        foreach ($patients as $appointment) {
            if ($appointment->patient === null) {
                continue;
            }

            $this->notifications->alert($appointment->patient, $title, $body, route('patient.dashboard'), $level);
        }
    }

    public function record(int $doctorId, ?int $appointmentId, ?int $actorId, string $event, ?string $explanation): void
    {
        QueueEvent::query()->create([
            'doctor_id' => $doctorId,
            'appointment_id' => $appointmentId,
            'actor_id' => $actorId,
            'event' => $event,
            'explanation' => $explanation,
        ]);
    }

    private function broadcast(LiveQueue $queue): LiveQueue
    {
        $queue->refresh()->load('doctor');
        QueueUpdated::dispatch($queue);

        return $queue;
    }
}
