<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminHospitalController extends Controller
{
    public function index(): View
    {
        return view('admin.hospitals', [
            'hospitals' => Hospital::orderBy('name')->get(),
            'staff' => User::whereIn('role', ['doctor', 'receptionist'])->with('hospital')->orderBy('name')->paginate(20, ['*'], 'staff_page'),
            'unassignedAppointments' => Appointment::whereNull('hospital_id')->with(['doctor.hospital', 'patient'])->orderByDesc('appointment_date')->paginate(20, ['*'], 'appointments_page'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:hospitals,name']]);
        Hospital::create($validated);

        return back()->with('success', 'Hospital created. Assign doctors and receptionists before accepting requests.');
    }

    public function assignStaff(Request $request, User $user): RedirectResponse
    {
        if (! $user->isDoctor() && ! $user->isReceptionist()) {
            abort(404);
        }
        $validated = $request->validate(['hospital_id' => ['present', 'nullable', 'exists:hospitals,id']]);
        DB::transaction(function () use ($user, $validated): void {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $hospitalId = isset($validated['hospital_id']) ? (int) $validated['hospital_id'] : null;
            if ($locked->isDoctor() && (int) $locked->hospital_id !== (int) $hospitalId) {
                $active = Appointment::where('doctor_id', $locked->id)->whereNotNull('hospital_id')
                    ->whereDate('appointment_date', '>=', today())->whereIn('status', Appointment::ACTIVE_STATUSES)
                    ->when($hospitalId !== null, fn (Builder $query): Builder => $query->where('hospital_id', '!=', $hospitalId))->exists();
                if ($active) {
                    throw ValidationException::withMessages(['hospital_id' => 'Resolve active appointments before moving this doctor to another hospital.']);
                }
            }
            $locked->update(['hospital_id' => $hospitalId]);
        });

        return back()->with('success', 'Hospital assignment saved. Historical appointments were not reassigned.');
    }

    public function assignAppointment(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate(['hospital_id' => ['required', 'exists:hospitals,id']]);
        DB::transaction(function () use ($appointment, $validated): void {
            $doctor = User::whereKey($appointment->doctor_id)->lockForUpdate()->firstOrFail();
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            if ($locked->hospital_id !== null || (int) $doctor->hospital_id !== (int) $validated['hospital_id']) {
                throw ValidationException::withMessages(['hospital_id' => 'Assign the doctor first and select that hospital for an unassigned appointment.']);
            }
            $locked->update(['hospital_id' => (int) $validated['hospital_id']]);
        });

        return back()->with('success', 'Historical appointment assigned. Its existing status was preserved.');
    }
}
