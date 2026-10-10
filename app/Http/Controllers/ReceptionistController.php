<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\QueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReceptionistController extends Controller
{
    public function __construct(
        protected AppointmentService $appointments,
        protected QueueService $queues,
    ) {}

    public function dashboard(): View
    {
        $visits = Appointment::query()
            ->whereDate('appointment_date', today())
            ->whereNotIn('status', ['cancelled'])
            ->with(['patient:id,name', 'doctor:id,name'])
            ->orderBy('doctor_id')
            ->orderBy('serial_number')
            ->get(['id', 'patient_id', 'doctor_id', 'serial_number', 'status', 'priority_type', 'visit_type', 'checked_in_at']);

        return view('receptionist.dashboard', compact('visits'));
    }

    public function create(): View
    {
        $doctors = User::doctors()->with('doctorProfile.department')->whereHas('doctorProfile')->orderBy('name')->get();

        return view('receptionist.walk-in', compact('doctors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_email' => ['required', 'email'],
            'doctor_id' => ['required', 'exists:users,id'],
            'priority_type' => ['required', 'in:normal,senior,follow_up,emergency'],
            'appointment_date' => ['required', 'date'],
        ]);

        $patient = User::query()->where('email', $validated['patient_email'])->where('role', 'patient')->first();

        if ($patient === null) {
            return back()->withErrors(['patient_email' => 'No patient account matches that email.'])->withInput();
        }

        $doctor = User::query()->findOrFail($validated['doctor_id']);

        try {
            $appointment = $this->appointments->book(
                $patient,
                $doctor,
                $validated['appointment_date'],
                $validated['priority_type'],
                'walk_in',
            );

            if ($appointment->appointment_date?->isToday()) {
                $this->queues->checkIn($appointment, $request->user());
            }
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('receptionist.dashboard')
            ->with('status', 'Walk-in booked. Serial #'.$appointment->serial_number.' · '.$appointment->priority_type.'.');
    }

    public function checkIn(Request $request, Appointment $appointment): RedirectResponse
    {
        try {
            $this->queues->checkIn($appointment, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Checked in serial #'.$appointment->serial_number.'.');
    }
}
