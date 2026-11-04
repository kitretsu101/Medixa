<?php

namespace App\Http\Controllers\Tracker;

use App\Http\Controllers\Controller;
use App\Models\TrackerInvitation;
use App\Services\Tracker\PrescriptionScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackerInvitationController extends Controller
{
    public function __construct(
        protected PrescriptionScheduleService $scheduleService,
    ) {}

    /**
     * Activate the medicine tracker from the patient dashboard popup.
     */
    public function activate(Request $request, TrackerInvitation $invitation): RedirectResponse
    {
        if ((int) $invitation->patient_id !== (int) $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized access to this invitation.');
        }

        $invitation->update([
            'status' => 'activated',
            'activated_at' => now(),
        ]);

        if ($invitation->prescription !== null) {
            $this->scheduleService->generateForPrescription($invitation->prescription);
        }

        return redirect()->route('tracker.app', ['activated' => 1])
            ->with('status', 'Medixa Medicine Tracker activated! Welcome to your Recovery Journey.');
    }

    /**
     * Dismiss the activation invitation ("Maybe Later").
     */
    public function dismiss(Request $request, TrackerInvitation $invitation): RedirectResponse
    {
        if ((int) $invitation->patient_id !== (int) $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized access to this invitation.');
        }

        $invitation->update([
            'status' => 'dismissed',
            'dismissed_at' => now(),
        ]);

        return back()->with('status', 'Tracker invitation dismissed. You can always activate it later from your prescriptions list.');
    }
}

