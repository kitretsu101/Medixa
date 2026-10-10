<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\LiveQueue;
use App\Models\User;
use App\Notifications\MedixaAlert;

class NotificationService
{
    public function alert(User $user, string $title, string $body, ?string $url = null, string $level = 'info'): void
    {
        $user->notify(new MedixaAlert($title, $body, $url, $level));
    }

    /**
     * Tell the patient whose serial was just called.
     */
    public function notifyCalledPatient(LiveQueue $queue): void
    {
        $appointment = Appointment::query()
            ->where('doctor_id', $queue->doctor_id)
            ->where('appointment_date', now()->toDateString())
            ->where('serial_number', $queue->current_running_serial)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in', 'waiting', 'in_progress'])
            ->with('patient')
            ->first();

        if ($appointment?->patient === null) {
            return;
        }

        $this->alert(
            $appointment->patient,
            'Your serial has been called',
            'Current serial is #'.$queue->current_running_serial.'. Please proceed to the chamber if this is your number.',
            route('patient.dashboard'),
        );
    }
}
