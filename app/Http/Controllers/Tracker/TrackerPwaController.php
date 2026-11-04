<?php

namespace App\Http\Controllers\Tracker;

use App\Http\Controllers\Controller;
use App\Models\MedicationDose;
use App\Models\MedicationSchedule;
use App\Models\RecoveryJourney;
use App\Models\RecoveryReward;
use App\Services\Tracker\PrescriptionScheduleService;
use App\Services\Tracker\RecoveryJourneyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackerPwaController extends Controller
{
    public function __construct(
        protected RecoveryJourneyService $journeyService,
        protected PrescriptionScheduleService $scheduleService,
    ) {}

    /**
     * Display the standalone Medixa Medicine Tracker PWA interface.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $activeJourney = null;
        $activeSchedule = null;
        $todayDoses = collect();
        $allDosesCount = 0;
        $takenDosesCount = 0;
        $missedDosesCount = 0;
        $rewards = collect();
        $totalPoints = 0;
        $monthlyCalendar = [];

        if ($user !== null && $user->isPatient()) {
            // Find active journey or latest journey
            $activeJourney = RecoveryJourney::where('patient_id', $user->id)
                ->where('status', 'active')
                ->with(['schedule.prescription.doctor', 'rewards'])
                ->latest()
                ->first();

            if ($activeJourney === null) {
                // If no active journey, check if there's any completed journey
                $activeJourney = RecoveryJourney::where('patient_id', $user->id)
                    ->with(['schedule.prescription.doctor', 'rewards'])
                    ->latest()
                    ->first();
            }

            if ($activeJourney !== null) {
                $this->journeyService->updateJourneyState($activeJourney);
                $activeSchedule = $activeJourney->schedule;

                if ($activeSchedule !== null) {
                    $monthlyCalendar = $this->scheduleService->buildMonthlyCalendar($activeSchedule);

                    $todayDoses = MedicationDose::where('medication_schedule_id', $activeSchedule->id)
                        ->where('scheduled_date', $today)
                        ->orderBy('scheduled_time')
                        ->get();

                    $allDoses = MedicationDose::where('medication_schedule_id', $activeSchedule->id)
                        ->where('scheduled_date', '<=', $today)
                        ->get();

                    $allDosesCount = $allDoses->count();
                    $takenDosesCount = $allDoses->where('status', 'taken')->count();
                    $missedDosesCount = $allDoses->whereIn('status', ['missed', 'skipped'])->count();
                }

                $rewards = RecoveryReward::where('recovery_journey_id', $activeJourney->id)
                    ->latest()
                    ->get();

                $totalPoints = $rewards->sum('points');
            }
        }

        return view('tracker.app', compact(
            'user',
            'activeJourney',
            'activeSchedule',
            'todayDoses',
            'allDosesCount',
            'takenDosesCount',
            'missedDosesCount',
            'rewards',
            'totalPoints',
            'today',
            'monthlyCalendar',
        ));
    }

    /**
     * Offline fallback view for Service Worker.
     */
    public function offline(): View
    {
        return view('tracker.offline');
    }
}

