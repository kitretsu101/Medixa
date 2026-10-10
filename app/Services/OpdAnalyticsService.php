<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentFeedback;
use App\Models\Department;
use App\Models\User;

class OpdAnalyticsService
{
    public function __construct(protected QueueOptimizationService $queueOptimization) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $today = Appointment::query()->whereDate('appointment_date', today());

        $waits = Appointment::query()
            ->whereNotNull('checked_in_at')
            ->whereNotNull('consultation_started_at')
            ->latest('consultation_started_at')
            ->limit(200)
            ->get(['checked_in_at', 'consultation_started_at', 'doctor_id']);

        $waitMinutes = $waits->map(fn (Appointment $appointment): int => (int) $appointment->checked_in_at->diffInMinutes($appointment->consultation_started_at));

        $consults = Appointment::query()
            ->whereNotNull('consultation_started_at')
            ->whereNotNull('consultation_ended_at')
            ->latest('consultation_ended_at')
            ->limit(200)
            ->get(['consultation_started_at', 'consultation_ended_at']);

        $consultMinutes = $consults->map(fn (Appointment $appointment): int => (int) $appointment->consultation_started_at->diffInMinutes($appointment->consultation_ended_at));

        $past = Appointment::query()->whereDate('appointment_date', '<', today());
        $pastTotal = (clone $past)->count();
        $noShow = (clone $past)->where('status', 'no_show')->count();

        $feedback = AppointmentFeedback::query()
            ->selectRaw('count(*) as responses, avg(waiting_rating) as waiting, avg(doctor_rating) as doctor, avg(system_rating) as system_score')
            ->first();

        return [
            'today_total' => (clone $today)->count(),
            'today_completed' => (clone $today)->where('status', 'completed')->count(),
            'today_cancelled' => (clone $today)->where('status', 'cancelled')->count(),
            'today_no_show' => (clone $today)->where('status', 'no_show')->count(),
            'today_waiting' => (clone $today)->whereIn('status', ['checked_in', 'waiting'])->count(),
            'average_wait' => $waitMinutes->isEmpty() ? null : (int) round($waitMinutes->avg()),
            'median_wait' => $this->median($waitMinutes->all()),
            'max_wait' => $waitMinutes->isEmpty() ? null : $waitMinutes->max(),
            'average_consultation' => $consultMinutes->isEmpty() ? null : (int) round($consultMinutes->avg()),
            'measured_visits' => $waitMinutes->count(),
            'no_show_rate' => $pastTotal === 0 ? null : round(($noShow / $pastTotal) * 100, 1),
            'peak_hour' => $this->peakHour(),
            'departments' => $this->departments(),
            'doctors' => $this->doctors(),
            'patients' => [
                'total' => User::patients()->count(),
                'new' => User::patients()->whereDate('created_at', '>=', now()->subDays(30))->count(),
            ],
            'feedback' => [
                'responses' => (int) ($feedback->responses ?? 0),
                'waiting' => $feedback->waiting === null ? null : round((float) $feedback->waiting, 1),
                'doctor' => $feedback->doctor === null ? null : round((float) $feedback->doctor, 1),
                'system' => $feedback->system_score === null ? null : round((float) $feedback->system_score, 1),
            ],
            'comparison' => $this->queueOptimization->compareAlgorithms(),
        ];
    }

    private function peakHour(): ?string
    {
        $rows = Appointment::query()
            ->whereNotNull('checked_in_at')
            ->where('checked_in_at', '>=', now()->subDays(30))
            ->get(['checked_in_at']);

        if ($rows->isEmpty()) {
            return null;
        }

        $counts = [];

        foreach ($rows as $row) {
            $hour = (int) $row->checked_in_at->format('G');
            $counts[$hour] = ($counts[$hour] ?? 0) + 1;
        }

        arsort($counts);
        $hour = (int) array_key_first($counts);
        $end = ($hour + 1) % 24;

        return sprintf('%s – %s', $this->hourLabel($hour), $this->hourLabel($end));
    }

    /**
     * @return list<array{name: string, appointments: int}>
     */
    private function departments(): array
    {
        return Department::query()
            ->leftJoin('doctor_profiles', 'doctor_profiles.department_id', '=', 'departments.id')
            ->leftJoin('appointments', function ($join): void {
                $join->on('appointments.doctor_id', '=', 'doctor_profiles.user_id')
                    ->whereDate('appointments.appointment_date', today())
                    ->whereNotIn('appointments.status', ['cancelled']);
            })
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('appointments_count')
            ->selectRaw('departments.name as name, count(appointments.id) as appointments_count')
            ->get()
            ->map(fn (Department $department): array => [
                'name' => $department->name,
                'appointments' => (int) $department->appointments_count,
            ])
            ->all();
    }

    /**
     * @return list<array{name: string, today: int, completed: int, pending_reports: int}>
     */
    private function doctors(): array
    {
        return User::doctors()
            ->with('doctorProfile.department')
            ->withCount([
                'doctorAppointments as today_count' => fn ($query) => $query->whereDate('appointment_date', today())->whereNotIn('status', ['cancelled']),
                'doctorAppointments as completed_count' => fn ($query) => $query->where('status', 'completed'),
                'assignedReports as pending_reports_count' => fn ($query) => $query->where('review_status', 'pending')->where('upload_status', 'uploaded'),
            ])
            ->orderByDesc('today_count')
            ->limit(12)
            ->get()
            ->map(fn (User $doctor): array => [
                'name' => $doctor->name,
                'department' => $doctor->doctorProfile?->department?->name,
                'today' => (int) $doctor->today_count,
                'completed' => (int) $doctor->completed_count,
                'pending_reports' => (int) $doctor->pending_reports_count,
            ])
            ->all();
    }

    /**
     * @param  list<int>  $values
     */
    private function median(array $values): ?int
    {
        if ($values === []) {
            return null;
        }

        sort($values);

        return $values[(int) floor((count($values) - 1) / 2)];
    }

    private function hourLabel(int $hour): string
    {
        $suffix = $hour < 12 ? 'AM' : 'PM';
        $display = $hour % 12;

        if ($display === 0) {
            $display = 12;
        }

        return $display.' '.$suffix;
    }
}
