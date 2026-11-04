<?php

namespace App\Services\Tracker;

use App\Models\AdherenceRecord;
use App\Models\MedicationDose;
use App\Models\RecoveryJourney;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicationAdherenceService
{
    public function __construct(
        protected RecoveryJourneyService $journeyService,
    ) {}

    /**
     * Patient confirms a dose as taken.
     */
    public function confirmDose(MedicationDose $dose, User $patient): array
    {
        if ((int) $dose->patient_id !== (int) $patient->id) {
            throw ValidationException::withMessages(['dose' => 'Unauthorized dose access.']);
        }

        if ($dose->status === 'taken') {
            throw ValidationException::withMessages(['dose' => 'This dose has already been confirmed as taken.']);
        }

        return DB::transaction(function () use ($dose, $patient) {
            $dose->update([
                'status' => 'taken',
                'taken_at' => now(),
            ]);

            AdherenceRecord::create([
                'medication_dose_id' => $dose->id,
                'patient_id' => $patient->id,
                'status' => 'taken',
                'recorded_at' => now(),
            ]);

            $schedule = $dose->schedule;
            $journey = RecoveryJourney::where('medication_schedule_id', $schedule->id)->first();

            if ($journey !== null) {
                // Award points for confirmed dose
                $this->journeyService->awardBadge($journey, 'first_dose', 'First Step', 20, 'Took your very first scheduled dose!');
                $this->journeyService->updateJourneyState($journey);
            }

            return [
                'dose' => $dose->fresh(),
                'journey' => $journey?->fresh(),
                'calendar' => app(PrescriptionScheduleService::class)->buildMonthlyCalendar($schedule),
                'message' => 'Dose successfully recorded.',
            ];
        });
    }

    /**
     * Patient reports a dose as missed or skipped.
     */
    public function recordMissedOrSkipped(MedicationDose $dose, User $patient, string $status, ?string $reason = null): array
    {
        if ((int) $dose->patient_id !== (int) $patient->id) {
            throw ValidationException::withMessages(['dose' => 'Unauthorized dose access.']);
        }

        if (! in_array($status, ['missed', 'skipped'], true)) {
            $status = 'missed';
        }

        return DB::transaction(function () use ($dose, $patient, $status, $reason) {
            $dose->update([
                'status' => $status,
            ]);

            AdherenceRecord::create([
                'medication_dose_id' => $dose->id,
                'patient_id' => $patient->id,
                'status' => $status,
                'reason' => $reason,
                'recorded_at' => now(),
            ]);

            $schedule = $dose->schedule;
            $journey = RecoveryJourney::where('medication_schedule_id', $schedule->id)->first();

            if ($journey !== null) {
                $this->journeyService->updateJourneyState($journey);
            }

            $clinicalWarning = null;
            if ($journey !== null && $journey->consecutive_missed_days >= 2) {
                $clinicalWarning = 'Safety Alert: You have recorded missed medication for 2 consecutive days. Never take a double dose to make up for a missed one. Please contact your doctor or pharmacist for professional advice.';
            }

            return [
                'dose' => $dose->fresh(),
                'journey' => $journey?->fresh(),
                'calendar' => app(PrescriptionScheduleService::class)->buildMonthlyCalendar($schedule),
                'clinical_warning' => $clinicalWarning,
                'message' => 'Status updated.',
            ];
        });
    }
}

