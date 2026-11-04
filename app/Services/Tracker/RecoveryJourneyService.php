<?php

namespace App\Services\Tracker;

use App\Models\MedicationDose;
use App\Models\RecoveryJourney;
use App\Models\RecoveryReward;
use Carbon\Carbon;

class RecoveryJourneyService
{
    /**
     * Recompute and update the journey's day, adherence %, consecutive missed days, and MDP state.
     */
    public function updateJourneyState(RecoveryJourney $journey): RecoveryJourney
    {
        $schedule = $journey->schedule;
        if ($schedule === null) {
            return $journey;
        }

        $today = Carbon::today();
        $startDate = Carbon::parse($schedule->start_date)->startOfDay();

        // Calculate current day number (1 to duration_days)
        $diffDays = $startDate->diffInDays($today, false);
        $totalDays = max(1, $schedule->duration_days);
        $currentDay = max(1, min($totalDays, (int) $diffDays + 1));
        $journey->current_day = $currentDay;

        // Doses scheduled up to and including today
        $dueDoses = MedicationDose::where('medication_schedule_id', $schedule->id)
            ->where('scheduled_date', '<=', $today->toDateString())
            ->get();

        $totalDue = $dueDoses->count();
        $takenCount = $dueDoses->where('status', 'taken')->count();

        // Calculate adherence percentage
        $adherenceRate = $totalDue > 0 ? round(($takenCount / $totalDue) * 100, 2) : 100.00;
        $journey->adherence_rate = $adherenceRate;

        // Calculate consecutive missed days
        $consecutiveMissedDays = $this->calculateConsecutiveMissedDays($schedule->id, $today);
        $journey->consecutive_missed_days = $consecutiveMissedDays;

        // Calculate current streak of consecutive days with all doses taken
        $journey->current_streak = $this->calculateDoseStreak($schedule->id, $today);

        // Dynamic Stage Progression Thresholds based on Total Prescribed Days
        $sproutDay = max(2, (int) round($totalDays * 0.25));
        $growingDay = max($sproutDay + 1, (int) round($totalDays * 0.65));

        // State Machine Transition Rules (MDP-inspired deterministic state model)
        if ($consecutiveMissedDays >= 2 || ($totalDue >= 4 && $adherenceRate < 50.0)) {
            $journey->current_state = RecoveryJourney::STATE_WILTED;
        } elseif ($currentDay >= $totalDays && $adherenceRate >= 60.0) {
            $journey->current_state = RecoveryJourney::STATE_BLOOM;
            if ($journey->status === 'active') {
                $journey->status = 'completed';
                $journey->completed_at = now();
                $this->awardBadge($journey, 'full_bloom', 'Recovery in Bloom', 200, "Completed your prescribed {$totalDays}-day Recovery Journey in Full Bloom!");
            }
        } elseif ($currentDay >= $growingDay && $adherenceRate >= 55.0) {
            $journey->current_state = RecoveryJourney::STATE_GROWING;
            $this->awardBadge($journey, 'growing_stage', 'Flourishing Foliage', 75, "Reached the growing plant stage on Day {$currentDay} of {$totalDays}.");
        } elseif ($currentDay >= $sproutDay && $adherenceRate >= 50.0) {
            $journey->current_state = RecoveryJourney::STATE_SPROUT;
            $this->awardBadge($journey, 'sprout_stage', 'First Sprout', 50, "Sprouted from seed with consistent early adherence on Day {$currentDay}.");
        } else {
            $journey->current_state = RecoveryJourney::STATE_SEED;
        }

        $journey->save();

        return $journey;
    }

    /**
     * Count how many consecutive recent days had scheduled doses where NONE were taken.
     */
    public function calculateConsecutiveMissedDays(int $scheduleId, Carbon $today): int
    {
        $consecutive = 0;
        $checkDate = clone $today;

        // Check up to past 7 days backward
        for ($i = 0; $i < 7; $i++) {
            $dateStr = $checkDate->toDateString();

            $dayDoses = MedicationDose::where('medication_schedule_id', $scheduleId)
                ->where('scheduled_date', $dateStr)
                ->get();

            if ($dayDoses->isEmpty()) {
                $checkDate->subDay();
                continue;
            }

            $takenToday = $dayDoses->where('status', 'taken')->count();

            if ($takenToday === 0) {
                // Whole day was missed
                $consecutive++;
                $checkDate->subDay();
            } else {
                // Adherence achieved on this day, stop streak of misses
                break;
            }
        }

        return $consecutive;
    }

    /**
     * Calculate current streak of consecutive days with taken doses.
     */
    public function calculateDoseStreak(int $scheduleId, Carbon $today): int
    {
        $streak = 0;
        $checkDate = clone $today;

        for ($i = 0; $i < 30; $i++) {
            $dateStr = $checkDate->toDateString();

            $dayDoses = MedicationDose::where('medication_schedule_id', $scheduleId)
                ->where('scheduled_date', $dateStr)
                ->get();

            if ($dayDoses->isEmpty()) {
                $checkDate->subDay();
                continue;
            }

            $taken = $dayDoses->where('status', 'taken')->count();
            if ($taken > 0) {
                $streak++;
                $checkDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Award a gamification badge / points if not already unlocked.
     */
    public function awardBadge(RecoveryJourney $journey, string $rewardType, string $badgeName, int $points, string $desc): void
    {
        RecoveryReward::firstOrCreate(
            [
                'recovery_journey_id' => $journey->id,
                'reward_type' => $rewardType,
            ],
            [
                'patient_id' => $journey->patient_id,
                'badge_name' => $badgeName,
                'points' => $points,
                'description' => $desc,
                'unlocked_at' => now(),
            ]
        );
    }
}

