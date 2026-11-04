<?php

namespace App\Http\Controllers\Tracker;

use App\Http\Controllers\Controller;
use App\Models\MedicationDose;
use App\Models\RecoveryJourney;
use App\Models\RecoveryReward;
use App\Services\Tracker\MedicationAdherenceService;
use App\Services\Tracker\PrescriptionScheduleService;
use App\Services\Tracker\RecoveryJourneyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackerApiController extends Controller
{
    public function __construct(
        protected MedicationAdherenceService $adherenceService,
        protected RecoveryJourneyService $journeyService,
        protected PrescriptionScheduleService $scheduleService,
    ) {}

    /**
     * Get patient tracker dashboard data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $patient = $request->user();
        $today = Carbon::today()->toDateString();

        $journey = RecoveryJourney::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->with(['schedule.prescription.doctor', 'rewards'])
            ->latest()
            ->first();

        if ($journey === null) {
            $journey = RecoveryJourney::where('patient_id', $patient->id)
                ->with(['schedule.prescription.doctor', 'rewards'])
                ->latest()
                ->first();
        }

        if ($journey === null) {
            return response()->json([
                'has_active_journey' => false,
                'message' => 'No active medication journey found.',
            ]);
        }

        $this->journeyService->updateJourneyState($journey);
        $schedule = $journey->schedule;

        $todayDoses = MedicationDose::where('medication_schedule_id', $schedule->id)
            ->where('scheduled_date', $today)
            ->orderBy('scheduled_time')
            ->get();

        $allDosesToDate = MedicationDose::where('medication_schedule_id', $schedule->id)
            ->where('scheduled_date', '<=', $today)
            ->get();

        $totalDue = $allDosesToDate->count();
        $taken = $allDosesToDate->where('status', 'taken')->count();
        $missed = $allDosesToDate->whereIn('status', ['missed', 'skipped'])->count();

        $rewards = RecoveryReward::where('recovery_journey_id', $journey->id)->get();

        $clinicalWarning = null;
        if ($journey->consecutive_missed_days >= 2) {
            $clinicalWarning = 'Safety Alert: You have recorded missed doses for 2 consecutive days. Never take a double dose to make up for a missed one. Please consult your prescribing doctor or pharmacist.';
        }

        return response()->json([
            'has_active_journey' => true,
            'journey' => [
                'id' => $journey->id,
                'current_day' => $journey->current_day,
                'total_days' => $schedule->duration_days,
                'current_state' => $journey->current_state,
                'adherence_rate' => $journey->adherence_rate,
                'current_streak' => $journey->current_streak,
                'consecutive_missed_days' => $journey->consecutive_missed_days,
                'status' => $journey->status,
                'is_completed' => $journey->isCompleted(),
                'is_wilted' => $journey->isWilted(),
            ],
            'doctor' => [
                'name' => $schedule->prescription?->doctor?->name ?? 'Attending Doctor',
                'diagnosis' => $schedule->prescription?->diagnosis ?? '',
            ],
            'doses_today' => $todayDoses,
            'statistics' => [
                'total_due' => $totalDue,
                'taken' => $taken,
                'missed' => $missed,
                'adherence_rate' => $journey->adherence_rate,
                'streak_days' => $journey->current_streak,
                'points' => $rewards->sum('points'),
                'badges_count' => $rewards->count(),
            ],
            'rewards' => $rewards,
            'clinical_warning' => $clinicalWarning,
            'calendar' => $this->scheduleService->buildMonthlyCalendar($schedule),
        ]);
    }

    /**
     * Patient confirms a dose as taken.
     */
    public function confirmDose(Request $request, MedicationDose $dose): JsonResponse
    {
        $patient = $request->user();

        if ((int) $dose->patient_id !== (int) $patient->id) {
            return response()->json(['error' => 'Unauthorized dose access.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $result = $this->adherenceService->confirmDose($dose, $patient);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Patient reports a dose as missed.
     */
    public function reportMissed(Request $request, MedicationDose $dose): JsonResponse
    {
        $patient = $request->user();

        if ((int) $dose->patient_id !== (int) $patient->id) {
            return response()->json(['error' => 'Unauthorized dose access.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->adherenceService->recordMissedOrSkipped($dose, $patient, 'missed', $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Patient reports a dose as skipped with a clinical or personal reason.
     */
    public function skipDose(Request $request, MedicationDose $dose): JsonResponse
    {
        $patient = $request->user();

        if ((int) $dose->patient_id !== (int) $patient->id) {
            return response()->json(['error' => 'Unauthorized dose access.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $result = $this->adherenceService->recordMissedOrSkipped($dose, $patient, 'skipped', $validated['reason']);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Fetch full 7-day adherence history and dose logs.
     */
    public function history(Request $request): JsonResponse
    {
        $patient = $request->user();

        $journey = RecoveryJourney::where('patient_id', $patient->id)
            ->with(['schedule.doses' => fn ($q) => $q->orderBy('scheduled_date')->orderBy('scheduled_time')])
            ->latest()
            ->first();

        if ($journey === null || $journey->schedule === null) {
            return response()->json(['history' => []]);
        }

        $grouped = $journey->schedule->doses->groupBy(fn ($d) => $d->scheduled_date->toDateString());

        return response()->json([
            'history' => $grouped,
            'journey' => $journey,
        ]);
    }

    /**
     * Fetch rewards and unlocked achievements.
     */
    public function rewards(Request $request): JsonResponse
    {
        $patient = $request->user();

        $rewards = RecoveryReward::where('patient_id', $patient->id)
            ->latest()
            ->get();

        return response()->json([
            'total_points' => $rewards->sum('points'),
            'rewards' => $rewards,
        ]);
    }
}

