<?php

namespace App\Services\Tracker;

use App\Models\MedicationDose;
use App\Models\RecoveryJourney;
use App\Models\TrackerNotificationLog;
use App\Models\User;
use App\Notifications\MedixaAlert;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MedicationNotificationService
{
    /**
     * Send scheduled reminders for doses due today.
     *
     * @return int Number of sent reminders
     */
    public function sendDueDoseReminders(): int
    {
        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now();

        // Doses scheduled for today that are still pending
        $dueDoses = MedicationDose::where('scheduled_date', $today)
            ->where('status', 'pending')
            ->with(['patient', 'schedule'])
            ->get();

        $sentCount = 0;

        foreach ($dueDoses as $dose) {
            $patient = $dose->patient;
            if ($patient === null) {
                continue;
            }

            // Check if reminder was already sent today for this dose
            $alreadySent = TrackerNotificationLog::where('medication_dose_id', $dose->id)
                ->where('type', 'reminder')
                ->whereDate('created_at', $today)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            // Send in-app notification
            $patient->notify(new MedixaAlert(
                title: 'Time for your medication: '.$dose->medicine_name,
                body: "Prescribed dose: {$dose->dosage} ({$dose->slot}). Tap to confirm you have taken your medicine.",
                url: route('tracker.app'),
                level: 'info'
            ));

            TrackerNotificationLog::create([
                'patient_id' => $patient->id,
                'medication_dose_id' => $dose->id,
                'type' => 'reminder',
                'channel' => 'inapp',
                'sent_at' => now(),
                'status' => 'sent',
                'payload' => [
                    'medicine' => $dose->medicine_name,
                    'dosage' => $dose->dosage,
                    'slot' => $dose->slot,
                ],
            ]);

            $sentCount++;
        }

        return $sentCount;
    }

    /**
     * Trigger consecutive missed-dose safety warning if 2+ consecutive days missed.
     */
    public function checkAndWarnConsecutiveMissedDays(User $patient): bool
    {
        $activeJourney = RecoveryJourney::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->first();

        if ($activeJourney === null || $activeJourney->consecutive_missed_days < 2) {
            return false;
        }

        // Avoid spamming warning more than once per day
        $warnedToday = TrackerNotificationLog::where('patient_id', $patient->id)
            ->where('type', 'consecutive_warning')
            ->whereDate('created_at', Carbon::today()->toDateString())
            ->exists();

        if ($warnedToday) {
            return false;
        }

        $patient->notify(new MedixaAlert(
            title: 'Medication Safety Alert',
            body: 'You have missed doses for 2 consecutive days. Do not take a double dose. Please contact your doctor or pharmacist for guidance.',
            url: route('tracker.app'),
            level: 'warning'
        ));

        TrackerNotificationLog::create([
            'patient_id' => $patient->id,
            'type' => 'consecutive_warning',
            'channel' => 'inapp',
            'sent_at' => now(),
            'status' => 'sent',
            'payload' => [
                'consecutive_missed_days' => $activeJourney->consecutive_missed_days,
            ],
        ]);

        return true;
    }

    /**
     * Fetch unread notifications for tracker.
     *
     * @return Collection<int, TrackerNotificationLog>
     */
    public function getRecentNotificationLogs(User $patient): Collection
    {
        return TrackerNotificationLog::where('patient_id', $patient->id)
            ->latest()
            ->take(20)
            ->get();
    }
}

