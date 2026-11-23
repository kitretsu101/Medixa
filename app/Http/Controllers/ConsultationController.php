<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\NotificationService;
use App\Services\QueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function __construct(
        protected DoctorPortalController $portal,
        protected QueueService $queues,
        protected NotificationService $notifications,
    ) {}

    public function show(Request $request): View
    {
        $queue = $this->queues->queueFor($request->user());
        $appointment = Appointment::query()
            ->where('doctor_id', $request->user()->id)
            ->whereDate('appointment_date', today())
            ->where('serial_number', $queue->current_running_serial)
            ->with('patient')
            ->first();

        $history = null;
        $prescriptionForm = null;

        if ($appointment?->patient !== null) {
            $history = $this->portal->patientHistory($request, $appointment->patient)
                ->fragment('consultation-history');
            $prescriptionForm = $this->portal->prescriptionForm($request, $appointment)
                ->with('prescriptionAction', route('doctor.consultation.prescriptions.store', $appointment))
                ->fragment('consultation-prescription');
        }

        return view('doctor.consultation', compact('queue', 'appointment', 'history', 'prescriptionForm'));
    }

    public function next(Request $request): RedirectResponse
    {
        $doctor = $request->user();
        $result = $this->queues->incrementWithOutcome($this->queues->queueFor($doctor), $doctor->id);

        if ($result['advanced']) {
            $this->notifications->notifyCalledPatient($result['queue']);
        }

        return redirect()->route('doctor.consultation')->with(
            'consultation_status',
            $result['advanced'] ? 'Next patient called.' : 'No more patients waiting',
        );
    }

    public function storePrescription(Request $request, Appointment $appointment): RedirectResponse
    {
        return $this->portal->storePrescription($request, $appointment)
            ->setTargetUrl(route('doctor.consultation'));
    }
}
