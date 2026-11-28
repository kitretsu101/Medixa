<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\NotificationService;
use App\Services\QueueService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReceptionistController extends Controller
{
    public function __construct(
        protected AppointmentService $appointments,
        protected QueueService $queues,
        protected NotificationService $notifications,
    ) {}

    public function dashboard(Request $request): View
    {
        if ($request->user()->hospital_id === null) {
            return view('receptionist.dashboard', ['visits' => collect(), 'requests' => collect()]);
        }

        $visits = Appointment::query()
            ->where('hospital_id', $request->user()->hospital_id)
            ->whereDate('appointment_date', today())
            ->whereNotIn('status', ['cancelled'])
            ->with(['patient:id,name', 'doctor:id,name'])
            ->orderBy('doctor_id')
            ->orderBy('serial_number')
            ->get(['id', 'patient_id', 'doctor_id', 'serial_number', 'status', 'priority_type', 'visit_type', 'checked_in_at']);

        $requests = $this->bookingRequests($request, (int) $request->user()->hospital_id, 'appointment_oldest')
            ->where('status', 'pending')->take(10)->get();
        $doctors = User::doctors()->where('hospital_id', $request->user()->hospital_id)->orderBy('name')->get(['id', 'name']);

        return view('receptionist.dashboard', compact('visits', 'requests', 'doctors'));
    }

    public function create(Request $request): View
    {
        $hospitalId = $this->assignedHospitalId($request);
        $doctors = User::doctors()->where('hospital_id', $hospitalId)->with('doctorProfile.department')->whereHas('doctorProfile')->orderBy('name')->get();

        return view('receptionist.walk-in', compact('doctors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $hospitalId = $this->assignedHospitalId($request);
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

        $doctor = User::query()->where('hospital_id', $hospitalId)->findOrFail($validated['doctor_id']);

        try {
            $appointment = $this->appointments->book(
                $patient,
                $doctor,
                $validated['appointment_date'],
                $validated['priority_type'],
                'walk_in',
                $hospitalId,
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('receptionist.dashboard')
            ->with('status', 'Walk-in requested. Approve before check-in. Serial #'.$appointment->serial_number.' · '.$appointment->priority_type.'.');
    }

    public function checkIn(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->appointmentForHospital($request, $appointment);
        try {
            $this->queues->checkIn($appointment, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Checked in serial #'.$appointment->serial_number.'.');
    }

    public function appointments(Request $request): View
    {
        $hospitalId = $this->assignedHospitalId($request);
        $requests = $this->bookingRequests($request, $hospitalId)->paginate(20)->withQueryString();
        $doctors = User::doctors()->where('hospital_id', $hospitalId)->orderBy('name')->get(['id', 'name']);

        return view('receptionist.appointments', compact('requests', 'doctors'));
    }

    private function bookingRequests(Request $request, int $hospitalId, string $defaultSort = 'pending'): Builder
    {
        $filters = $request->validate([
            'doctor_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'doctor')->where('hospital_id', $hospitalId)],
            'appointment_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', Rule::in(['pending', 'appointment_newest', 'appointment_oldest', 'doctor', 'created_newest', 'created_oldest'])],
        ]);
        $query = Appointment::where('hospital_id', $hospitalId)->with(['patient', 'doctor', 'hospital']);
        if (! empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }
        if (! empty($filters['appointment_date'])) {
            $query->whereDate('appointment_date', $filters['appointment_date']);
        }

        return match ($filters['sort'] ?? $defaultSort) {
            'appointment_newest' => $query->orderByDesc('appointment_date')->orderByDesc('id'),
            'appointment_oldest' => $query->orderBy('appointment_date')->orderBy('serial_number')->orderBy('id'),
            'doctor' => $query->orderBy(User::select('name')->whereColumn('users.id', 'appointments.doctor_id'))->orderBy('appointment_date')->orderBy('id'),
            'created_newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'created_oldest' => $query->orderBy('created_at')->orderBy('id'),
            default => $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->orderByDesc('appointment_date')->orderByDesc('id'),
        };
    }

    public function showAppointment(Request $request, Appointment $appointment): View
    {
        $this->appointmentForHospital($request, $appointment);
        $appointment->load(['patient', 'doctor', 'hospital']);

        return view('receptionist.appointment', compact('appointment'));
    }

    public function approveAppointment(Request $request, Appointment $appointment): RedirectResponse
    {
        return $this->changeAppointmentStatus($request, $appointment, 'approved');
    }

    public function rejectAppointment(Request $request, Appointment $appointment): RedirectResponse
    {
        return $this->changeAppointmentStatus($request, $appointment, 'rejected');
    }

    private function assignedHospitalId(Request $request): int
    {
        if ($request->user()->hospital_id === null) {
            abort(403, 'No hospital is assigned to your account. Contact an administrator.');
        }

        return (int) $request->user()->hospital_id;
    }

    private function appointmentForHospital(Request $request, Appointment $appointment): void
    {
        if ((int) $appointment->hospital_id !== $this->assignedHospitalId($request)) {
            abort(404);
        }
    }

    private function changeAppointmentStatus(Request $request, Appointment $appointment, string $status): RedirectResponse
    {
        $hospitalId = $this->assignedHospitalId($request);
        $updated = DB::transaction(function () use ($appointment, $hospitalId, $status): Appointment {
            $locked = Appointment::whereKey($appointment->id)->where('hospital_id', $hospitalId)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['appointment' => 'Only pending appointments can be approved or rejected.']);
            }
            $locked->update(['status' => $status]);

            return $locked;
        });
        $updated->load(['patient', 'doctor']);
        if ($updated->patient !== null) {
            $this->notifications->alert($updated->patient, 'Appointment '.$status, 'Your appointment on '.$updated->appointment_date->format('d M Y').' was '.$status.'.', route('patient.appointments.index'));
        }
        if ($status === 'approved' && $updated->doctor !== null) {
            $this->notifications->alert($updated->doctor, 'Appointment approved', 'An appointment was approved by your hospital reception.', route('doctor.dashboard'));
        }

        return back()->with('status', 'Appointment '.$status.'.');
    }
}
