<?php

namespace App\Services\Tracker;

use App\Models\MedicationDose;
use App\Models\MedicationSchedule;
use App\Models\Prescription;
use App\Models\RecoveryJourney;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PrescriptionScheduleService
{
    /**
     * Standard time mappings for medication slots.
     */
    public const DEFAULT_SLOT_TIMES = [
        'morning' => '08:00:00',
        'afternoon' => '13:00:00',
        'evening' => '18:00:00',
        'night' => '21:00:00',
    ];

    /**
     * Generate or retrieve medication schedule and recovery journey for a prescription.
     */
    public function generateForPrescription(Prescription $prescription): MedicationSchedule
    {
        return DB::transaction(function () use ($prescription) {
            // Check if active schedule already exists to prevent duplicate generation
            $existing = MedicationSchedule::where('prescription_id', $prescription->id)
                ->where('status', 'active')
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $medicines = is_array($prescription->medicines) ? $prescription->medicines : [];
            $startDate = Carbon::today();
            $durationDays = $this->determineMaxDurationDays($medicines, $startDate);

            $endDate = (clone $startDate)->addDays($durationDays - 1);

            $schedule = MedicationSchedule::create([
                'patient_id' => $prescription->patient_id,
                'prescription_id' => $prescription->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'duration_days' => $durationDays,
                'status' => 'active',
                'timezone' => config('app.timezone', 'UTC'),
            ]);

            // Generate dose entries for each day
            foreach ($medicines as $med) {
                $name = trim($med['name'] ?? '');
                $dosage = trim($med['dosage'] ?? '1 dose');
                $frequency = trim($med['frequency'] ?? '1-0-1');
                $medDuration = $this->parseDurationDays($med['duration'] ?? null) ?? $durationDays;

                if (empty($name)) {
                    continue;
                }

                $slots = $this->parseFrequencySlots($frequency);

                for ($d = 0; $d < $medDuration; $d++) {
                    $doseDate = (clone $startDate)->addDays($d)->toDateString();

                    foreach ($slots as $slot) {
                        $time = self::DEFAULT_SLOT_TIMES[$slot] ?? '08:00:00';

                        MedicationDose::firstOrCreate(
                            [
                                'medication_schedule_id' => $schedule->id,
                                'medicine_name' => $name,
                                'scheduled_date' => $doseDate,
                                'scheduled_time' => $time,
                            ],
                            [
                                'patient_id' => $prescription->patient_id,
                                'dosage' => $dosage,
                                'slot' => $slot,
                                'instructions' => $prescription->instructions,
                                'status' => 'pending',
                            ]
                        );
                    }
                }
            }

            // Create or initialize the Recovery Journey
            RecoveryJourney::firstOrCreate(
                [
                    'prescription_id' => $prescription->id,
                    'medication_schedule_id' => $schedule->id,
                ],
                [
                    'patient_id' => $prescription->patient_id,
                    'current_day' => 1,
                    'current_state' => RecoveryJourney::STATE_SEED,
                    'adherence_rate' => 100.00,
                    'current_streak' => 0,
                    'consecutive_missed_days' => 0,
                    'status' => 'active',
                    'started_at' => now(),
                ]
            );

            return $schedule;
        });
    }

    /**
     * Supersede and safely regenerate future pending doses if a prescription changes.
     */
    public function regenerateSchedule(Prescription $prescription): MedicationSchedule
    {
        return DB::transaction(function () use ($prescription) {
            $currentSchedule = MedicationSchedule::where('prescription_id', $prescription->id)
                ->where('status', 'active')
                ->first();

            if ($currentSchedule !== null) {
                // Cancel future pending doses only, preserve historical doses and adherence records
                MedicationDose::where('medication_schedule_id', $currentSchedule->id)
                    ->where('scheduled_date', '>=', Carbon::today()->toDateString())
                    ->where('status', 'pending')
                    ->delete();

                $currentSchedule->update(['status' => 'superseded']);
            }

            return $this->generateForPrescription($prescription);
        });
    }

    /**
     * Parse duration string (e.g. "7 days", "2 weeks", "1 month", "2 months", "5") into an integer of days.
     * Uses calendar dates to accurately account for 30-day, 31-day, and February lengths.
     */
    public function parseDurationDays(?string $durationStr, ?Carbon $startDate = null): ?int
    {
        if (empty($durationStr)) {
            return null;
        }

        $durationStr = strtolower(trim($durationStr));
        $start = $startDate ? (clone $startDate)->startOfDay() : Carbon::today()->startOfDay();

        // Month parsing: "1 month", "2 months", "3 mos", "1 mth", etc.
        if (preg_match('/(\d+)\s*(month|months|mo|mos|mth|mths)\b/', $durationStr, $matches)) {
            $months = max(1, (int) $matches[1]);
            $end = (clone $start)->addMonths($months);

            return (int) $start->diffInDays($end);
        }

        // Week parsing: "1 week", "2 weeks", "3 wk", etc.
        if (preg_match('/(\d+)\s*(week|weeks|wk|wks|w)\b/', $durationStr, $matches)) {
            return ((int) $matches[1]) * 7;
        }

        // Day parsing: "5 days", "7 days", "10 d", "30 days"
        if (preg_match('/(\d+)\s*(day|days|d)\b/', $durationStr, $matches)) {
            return max(1, (int) $matches[1]);
        }

        // Plain digits: "5", "10", "30"
        if (preg_match('/^\s*(\d+)\s*$/', $durationStr, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return null;
    }

    /**
     * Calculate maximum duration across all medicines.
     * Only defaults to 7 days if no medicine has an explicitly defined duration.
     *
     * @param  array<int, array<string, mixed>>  $medicines
     */
    public function determineMaxDurationDays(array $medicines, ?Carbon $startDate = null): int
    {
        $maxDays = null;

        foreach ($medicines as $med) {
            $days = $this->parseDurationDays($med['duration'] ?? null, $startDate);
            if ($days !== null) {
                if ($maxDays === null || $days > $maxDays) {
                    $maxDays = min($days, 365); // Cap at 365 days (1 year max course)
                }
            }
        }

        return $maxDays ?? 7;
    }

    /**
     * Build a structured month-by-month calendar breakdown for the medication schedule.
     * Accurately reflects 30-day, 31-day, and 28/29-day calendar months and daily seed/bloom stages.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildMonthlyCalendar(MedicationSchedule $schedule): array
    {
        $startDate = Carbon::parse($schedule->start_date)->startOfDay();
        $endDate = Carbon::parse($schedule->end_date)->startOfDay();
        $totalDays = max(1, $schedule->duration_days);

        $dosesByDate = MedicationDose::where('medication_schedule_id', $schedule->id)
            ->orderBy('scheduled_time')
            ->get()
            ->groupBy('scheduled_date');

        $today = Carbon::today()->startOfDay();
        $todayStr = $today->toDateString();

        $sproutThreshold = max(2, (int) round($totalDays * 0.25));
        $growingThreshold = max($sproutThreshold + 1, (int) round($totalDays * 0.65));

        $months = [];
        $currentDate = clone $startDate;
        $dayOfJourney = 1;

        while ($currentDate->lte($endDate)) {
            $monthKey = $currentDate->format('Y-m');
            $monthName = $currentDate->format('F Y');
            $daysInThisMonth = (int) $currentDate->daysInMonth;

            if (! isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'month_key' => $monthKey,
                    'month_name' => $monthName,
                    'month_short' => $currentDate->format('M'),
                    'year' => (int) $currentDate->format('Y'),
                    'days_in_calendar_month' => $daysInThisMonth,
                    'is_current_month' => $monthKey === $today->format('Y-m'),
                    'days' => [],
                ];
            }

            $dateStr = $currentDate->toDateString();
            $dosesForDay = $dosesByDate->get($dateStr, collect());
            $totalDoses = $dosesForDay->count();
            $takenDoses = $dosesForDay->where('status', 'taken')->count();
            $missedDoses = $dosesForDay->whereIn('status', ['missed', 'skipped'])->count();

            // Status of this day
            $dayStatus = 'upcoming';
            if ($dateStr === $todayStr) {
                $dayStatus = ($totalDoses > 0 && $takenDoses === $totalDoses) ? 'completed' : 'today';
            } elseif ($currentDate->lt($today)) {
                if ($totalDoses > 0 && $takenDoses === $totalDoses) {
                    $dayStatus = 'completed';
                } elseif ($missedDoses > 0 || ($totalDoses > 0 && $takenDoses === 0)) {
                    $dayStatus = 'missed';
                } else {
                    $dayStatus = 'partial';
                }
            }

            // Seed to Bloom progression stage for this day
            $stage = 'seed';
            if ($dayOfJourney >= $totalDays) {
                $stage = 'bloom';
            } elseif ($dayOfJourney >= $growingThreshold) {
                $stage = 'growing';
            } elseif ($dayOfJourney >= $sproutThreshold) {
                $stage = 'sprout';
            }

            $months[$monthKey]['days'][] = [
                'journey_day' => $dayOfJourney,
                'calendar_day' => (int) $currentDate->format('j'),
                'date' => $dateStr,
                'weekday' => $currentDate->format('D'),
                'status' => $dayStatus,
                'stage' => $stage,
                'total_doses' => $totalDoses,
                'taken_doses' => $takenDoses,
                'is_today' => $dateStr === $todayStr,
                'medicine_names' => $dosesForDay->pluck('medicine_name')->unique()->values()->all(),
            ];

            $dayOfJourney++;
            $currentDate->addDay();
        }

        return array_values($months);
    }

    /**
     * Parse frequency strings into array of time slots ('morning', 'afternoon', 'evening', 'night').
     *
     * @return array<int, string>
     */
    public function parseFrequencySlots(string $frequency): array
    {
        $cleaned = strtolower(trim($frequency));

        // Format: "1-0-1" or "1+0+1" or "1 0 1" (3 numbers)
        if (preg_match('/^([0-9])[\s\-\+\/]+([0-9])[\s\-\+\/]+([0-9])$/', $cleaned, $matches)) {
            $slots = [];
            if ((int) $matches[1] > 0) {
                $slots[] = 'morning';
            }
            if ((int) $matches[2] > 0) {
                $slots[] = 'afternoon';
            }
            if ((int) $matches[3] > 0) {
                $slots[] = 'night';
            }

            return ! empty($slots) ? $slots : ['morning'];
        }

        // Format: 4 slots "1-1-1-1" or "1+1+1+1"
        if (preg_match('/^([0-9])[\s\-\+\/]+([0-9])[\s\-\+\/]+([0-9])[\s\-\+\/]+([0-9])$/', $cleaned, $matches)) {
            $slots = [];
            if ((int) $matches[1] > 0) {
                $slots[] = 'morning';
            }
            if ((int) $matches[2] > 0) {
                $slots[] = 'afternoon';
            }
            if ((int) $matches[3] > 0) {
                $slots[] = 'evening';
            }
            if ((int) $matches[4] > 0) {
                $slots[] = 'night';
            }

            return ! empty($slots) ? $slots : ['morning'];
        }

        // Textual frequency checks
        if (str_contains($cleaned, 'four') || str_contains($cleaned, 'qid') || str_contains($cleaned, '4 time')) {
            return ['morning', 'afternoon', 'evening', 'night'];
        }

        if (str_contains($cleaned, 'three') || str_contains($cleaned, 'tid') || str_contains($cleaned, '3 time')) {
            return ['morning', 'afternoon', 'night'];
        }

        if (str_contains($cleaned, 'twice') || str_contains($cleaned, 'bid') || str_contains($cleaned, '2 time')) {
            return ['morning', 'night'];
        }

        if (str_contains($cleaned, 'night') || str_contains($cleaned, 'bedtime') || str_contains($cleaned, 'sleep')) {
            return ['night'];
        }

        if (str_contains($cleaned, 'afternoon') || str_contains($cleaned, 'noon')) {
            return ['afternoon'];
        }

        if (str_contains($cleaned, 'evening')) {
            return ['evening'];
        }

        // Default once daily (morning)
        return ['morning'];
    }
}

