<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(protected QueueService $queues) {}

    public const DAILY_CAP = 20;

    public const EMERGENCY_BUFFER = 2;

    /** @var list<string> */
    public const PATIENT_PRIORITIES = ['normal', 'senior', 'follow_up'];

    /**
     * Book an OPD appointment and assign the next serial for that doctor and date.
     *
     * @throws ValidationException
     */
    public function book(User $patient, User $doctor, string $date, string $priority = 'normal', string $visitType = 'scheduled'): Appointment
    {
        if (! $patient->isPatient()) {
            throw ValidationException::withMessages([
                'patient' => 'Only a patient account can book an appointment.',
            ]);
        }

        if (! $doctor->isDoctor() || $doctor->doctorProfile === null) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Select a doctor who is on the hospital directory.',
            ]);
        }

        try {
            $appointmentDate = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'appointment_date' => 'Enter a valid appointment date.',
            ]);
        }

        if ($appointmentDate->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'appointment_date' => 'Appointments cannot be booked in the past.',
            ]);
        }

        if (! array_key_exists($priority, QueueOptimizationService::BASE_SCORES)) {
            throw ValidationException::withMessages([
                'priority_type' => 'Choose a valid appointment priority.',
            ]);
        }

        $dateString = $appointmentDate->toDateString();

        return DB::transaction(function () use ($patient, $doctor, $dateString, $priority, $visitType): Appointment {
            User::query()->whereKey($doctor->id)->lockForUpdate()->first();

            $rows = Appointment::query()
                ->where('doctor_id', $doctor->id)
                ->where('appointment_date', $dateString)
                ->lockForUpdate()
                ->get();

            $active = $rows->whereIn('status', Appointment::ACTIVE_STATUSES);

            if ($active->contains(fn (Appointment $row): bool => (int) $row->patient_id === (int) $patient->id)) {
                throw ValidationException::withMessages([
                    'appointment_date' => 'You already have an active appointment with this doctor on that date.',
                ]);
            }

            $occupying = $rows->whereIn('status', [...Appointment::ACTIVE_STATUSES, 'completed']);
            $emergencyCount = $occupying->where('priority_type', 'emergency')->count();
            $routineCount = $occupying->count() - $emergencyCount;
            $routineCap = self::DAILY_CAP - self::EMERGENCY_BUFFER;

            if ($priority === 'emergency' && $occupying->count() >= self::DAILY_CAP) {
                throw ValidationException::withMessages([
                    'appointment_date' => 'No slots remain for this doctor on that date.',
                ]);
            }

            if ($priority !== 'emergency' && $routineCount >= $routineCap) {
                throw ValidationException::withMessages([
                    'appointment_date' => 'Routine slots are full. '.$routineCap.' are open for booked visits, and '.self::EMERGENCY_BUFFER.' are held for emergencies.',
                ]);
            }

            $maxSerial = (int) ($rows->max('serial_number') ?? 0);

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => $dateString,
                'serial_number' => $maxSerial + 1,
                'status' => 'pending',
                'priority_type' => $priority,
                'visit_type' => $visitType,
            ]);

            $this->queues->record(
                (int) $doctor->id,
                $appointment->id,
                (int) $patient->id,
                'appointment_booked',
                'Appointment booked with priority '.$priority.'.',
            );

            return $appointment;
        });
    }
}
