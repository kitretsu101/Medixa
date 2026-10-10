<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\LiveQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class QueueOptimizationService
{
    public const BASE_SCORES = [
        'emergency' => 100,
        'senior' => 70,
        'follow_up' => 50,
        'normal' => 10,
    ];

    public const AGING_CAP = 80;

    public const BASELINE_MINUTES = 12;

    public const HISTORY_SAMPLE = 5;

    public const ARRIVAL_BUFFER_MINUTES = 15;

    /** @var list<int> */
    public const MILESTONES = [10, 5, 3, 1];

    /** @var list<string> */
    public const WAITING_STATUSES = ['checked_in', 'waiting'];

    /**
     * @param  list<array{id: int, priority: string, arrived: int}>  $arrivals
     * @return array{name: string, average_wait: int, median_wait: int, max_wait: int, emergency_wait: int, senior_wait: int, normal_wait: int}
     */
    public function simulate(array $arrivals, string $algorithm, int $consultationMinutes = self::BASELINE_MINUTES): array
    {
        $waiting = [];
        $served = [];
        $cursor = 0;
        $now = 0;
        $total = count($arrivals);

        while (count($served) < $total) {
            while ($cursor < $total && $arrivals[$cursor]['arrived'] <= $now) {
                $waiting[] = $arrivals[$cursor];
                $cursor++;
            }

            if ($waiting === []) {
                $now = $arrivals[$cursor]['arrived'];

                continue;
            }

            $index = $this->pickIndex($waiting, $now, $algorithm);
            $chosen = $waiting[$index];
            array_splice($waiting, $index, 1);
            $served[] = [
                'priority' => $chosen['priority'],
                'wait' => $now - $chosen['arrived'],
            ];
            $now += $consultationMinutes;
        }

        return $this->summarize($algorithm, $served);
    }

    /**
     * @return array{sample_size: int, consultation_minutes: int, label: string, algorithms: list<array{name: string, average_wait: int, median_wait: int, max_wait: int, emergency_wait: int, senior_wait: int, normal_wait: int}>}
     */
    public function compareAlgorithms(): array
    {
        $arrivals = $this->sampleArrivals();

        return [
            'sample_size' => count($arrivals),
            'consultation_minutes' => self::BASELINE_MINUTES,
            'label' => 'Baseline / simulated comparison. Figures are computed from a fixed arrival sample, not from recorded hospital history.',
            'algorithms' => [
                $this->simulate($arrivals, 'fifo'),
                $this->simulate($arrivals, 'priority'),
                $this->simulate($arrivals, 'priority_aging'),
            ],
        ];
    }

    public function score(string $priority, int $waitedMinutes): int
    {
        $base = self::BASE_SCORES[$priority] ?? self::BASE_SCORES['normal'];
        $aging = min(self::AGING_CAP, max(0, $waitedMinutes));

        if ($priority === 'emergency') {
            return 1000 + $base + $aging;
        }

        return $base + $aging;
    }

    public function waitedMinutes(Appointment $appointment, ?Carbon $now = null): int
    {
        if ($appointment->checked_in_at === null) {
            return 0;
        }

        return (int) $appointment->checked_in_at->diffInMinutes($now ?? now());
    }

    public function scoreAppointment(Appointment $appointment, ?Carbon $now = null): int
    {
        return $this->score((string) $appointment->priority_type, $this->waitedMinutes($appointment, $now));
    }

    public function explain(Appointment $appointment, ?Carbon $now = null): string
    {
        $priority = (string) $appointment->priority_type;
        $label = $this->label($priority);

        if ($priority === 'emergency') {
            return 'Priority: Emergency';
        }

        $base = self::BASE_SCORES[$priority] ?? self::BASE_SCORES['normal'];

        if ($this->scoreAppointment($appointment, $now) > $base) {
            return 'Priority increased because the patient has exceeded the configured waiting threshold.';
        }

        return 'Priority: '.$label;
    }

    public function label(string $priority): string
    {
        return match ($priority) {
            'emergency' => 'Emergency',
            'senior' => 'Senior citizen',
            'follow_up' => 'Follow-up',
            default => 'Normal',
        };
    }

    public function averageConsultationMinutes(int $doctorId): int
    {
        $samples = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereNotNull('consultation_started_at')
            ->whereNotNull('consultation_ended_at')
            ->latest('consultation_ended_at')
            ->limit(30)
            ->get(['consultation_started_at', 'consultation_ended_at']);

        if ($samples->count() < self::HISTORY_SAMPLE) {
            return self::BASELINE_MINUTES;
        }

        $average = $samples->avg(function (Appointment $appointment): int {
            return (int) $appointment->consultation_started_at->diffInMinutes($appointment->consultation_ended_at);
        });

        return max(5, (int) round((float) $average));
    }

    public function nextWaiting(int $doctorId, ?Carbon $now = null): ?Appointment
    {
        $waiting = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', ($now ?? now())->toDateString())
            ->whereIn('status', self::WAITING_STATUSES)
            ->get();

        return $this->rank($waiting, $now)->first();
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @return Collection<int, Appointment>
     */
    public function rank(Collection $appointments, ?Carbon $now = null): Collection
    {
        return $appointments
            ->sort(function (Appointment $left, Appointment $right) use ($now): int {
                $score = $this->scoreAppointment($right, $now) <=> $this->scoreAppointment($left, $now);

                if ($score !== 0) {
                    return $score;
                }

                $checkIn = ($left->checked_in_at?->getTimestamp() ?? PHP_INT_MAX)
                    <=> ($right->checked_in_at?->getTimestamp() ?? PHP_INT_MAX);

                if ($checkIn !== 0) {
                    return $checkIn;
                }

                return $left->serial_number <=> $right->serial_number;
            })
            ->values();
    }

    public function patientsAhead(Appointment $appointment, ?LiveQueue $queue = null): int
    {
        if (in_array($appointment->status, [...self::WAITING_STATUSES, 'in_progress'], true)) {
            if ($appointment->status === 'in_progress') {
                return 0;
            }

            $waiting = Appointment::query()
                ->where('doctor_id', $appointment->doctor_id)
                ->whereDate('appointment_date', $appointment->appointment_date)
                ->whereIn('status', self::WAITING_STATUSES)
                ->get();

            $ranked = $this->rank($waiting);
            $position = $ranked->search(fn (Appointment $row): bool => $row->id === $appointment->id);

            return $position === false ? 0 : (int) $position;
        }

        $current = (int) ($queue?->current_running_serial ?? 0);

        return max(0, (int) $appointment->serial_number - $current);
    }

    /**
     * @return array{
     *     patients_ahead: int,
     *     estimated_wait_minutes: int,
     *     average_consultation_minutes: int,
     *     using_history: bool,
     *     delay_minutes: int,
     *     session_status: string,
     *     delay_message: string|null,
     *     explanation: string,
     *     priority_label: string,
     *     recommended_arrival: string|null,
     *     checked_in: bool
     * }
     */
    public function snapshot(Appointment $appointment): array
    {
        $queue = $appointment->relationLoaded('doctor')
            ? $appointment->doctor?->liveQueue
            : LiveQueue::query()->where('doctor_id', $appointment->doctor_id)->first();

        $samples = Appointment::query()
            ->where('doctor_id', $appointment->doctor_id)
            ->whereNotNull('consultation_started_at')
            ->whereNotNull('consultation_ended_at')
            ->count();

        $average = $this->averageConsultationMinutes((int) $appointment->doctor_id);
        $ahead = $this->patientsAhead($appointment, $queue);
        $delay = (int) ($queue?->delay_minutes ?? 0);
        $session = (string) ($queue?->session_status ?? 'not_started');
        $estimate = ($ahead * $average) + $delay;
        $checkedIn = $appointment->checked_in_at !== null;
        $recommended = null;

        if (! $checkedIn && $appointment->appointment_date?->isToday()) {
            $recommended = now()->addMinutes($estimate + self::ARRIVAL_BUFFER_MINUTES)->format('g:i A');
        }

        $delayMessage = null;

        if ($session === 'paused') {
            $delayMessage = 'The queue is paused.';
        } elseif ($delay > 0) {
            $delayMessage = 'Doctor is currently delayed by approximately '.$delay.' minutes.';
        }

        return [
            'patients_ahead' => $ahead,
            'estimated_wait_minutes' => $estimate,
            'average_consultation_minutes' => $average,
            'using_history' => $samples >= self::HISTORY_SAMPLE,
            'delay_minutes' => $delay,
            'session_status' => $session,
            'delay_message' => $delayMessage,
            'explanation' => $this->explain($appointment),
            'priority_label' => $this->label((string) $appointment->priority_type),
            'recommended_arrival' => $recommended,
            'checked_in' => $checkedIn,
        ];
    }

    /**
     * @return list<array{id: int, priority: string, arrived: int}>
     */
    public function sampleArrivals(): array
    {
        $pattern = [
            'normal', 'normal', 'senior', 'normal', 'follow_up', 'normal', 'senior', 'normal',
            'emergency', 'normal', 'follow_up', 'normal', 'senior', 'normal', 'normal',
            'emergency', 'normal', 'senior', 'follow_up', 'normal',
        ];

        $arrivals = [];

        foreach ($pattern as $index => $priority) {
            $arrivals[] = [
                'id' => $index + 1,
                'priority' => $priority,
                'arrived' => $index * 8,
            ];
        }

        return $arrivals;
    }

    /**
     * A normal patient who arrived first, then a stream of seniors.
     * Used to show that aging serves the waiting normal patient sooner than pure priority.
     *
     * @return list<array{id: int, priority: string, arrived: int}>
     */
    public function starvationSample(): array
    {
        $arrivals = [
            ['id' => 1, 'priority' => 'senior', 'arrived' => 0],
            ['id' => 2, 'priority' => 'normal', 'arrived' => 1],
        ];

        for ($index = 0; $index < 8; $index++) {
            $arrivals[] = [
                'id' => $index + 3,
                'priority' => 'senior',
                'arrived' => 12 * ($index + 1),
            ];
        }

        return $arrivals;
    }

    /**
     * @param  list<array{id: int, priority: string, arrived: int}>  $waiting
     */
    private function pickIndex(array $waiting, int $now, string $algorithm): int
    {
        $best = 0;

        foreach ($waiting as $index => $candidate) {
            if ($index === 0) {
                continue;
            }

            $current = $waiting[$best];

            if ($this->outranks($candidate, $current, $now, $algorithm)) {
                $best = $index;
            }
        }

        return $best;
    }

    /**
     * @param  array{id: int, priority: string, arrived: int}  $candidate
     * @param  array{id: int, priority: string, arrived: int}  $current
     */
    private function outranks(array $candidate, array $current, int $now, string $algorithm): bool
    {
        if ($algorithm === 'fifo') {
            return $candidate['arrived'] < $current['arrived'];
        }

        $candidateScore = $algorithm === 'priority'
            ? (self::BASE_SCORES[$candidate['priority']] ?? 10)
            : $this->score($candidate['priority'], $now - $candidate['arrived']);
        $currentScore = $algorithm === 'priority'
            ? (self::BASE_SCORES[$current['priority']] ?? 10)
            : $this->score($current['priority'], $now - $current['arrived']);

        if ($candidateScore === $currentScore) {
            return $candidate['arrived'] < $current['arrived'];
        }

        return $candidateScore > $currentScore;
    }

    /**
     * @param  list<array{priority: string, wait: int}>  $served
     * @return array{name: string, average_wait: int, median_wait: int, max_wait: int, emergency_wait: int, senior_wait: int, normal_wait: int}
     */
    private function summarize(string $algorithm, array $served): array
    {
        $waits = array_column($served, 'wait');
        sort($waits);
        $count = count($waits);
        $middle = (int) floor(($count - 1) / 2);

        return [
            'name' => match ($algorithm) {
                'priority' => 'Priority',
                'priority_aging' => 'Priority + aging',
                default => 'FIFO',
            },
            'average_wait' => $count === 0 ? 0 : (int) round(array_sum($waits) / $count),
            'median_wait' => $waits[$middle] ?? 0,
            'max_wait' => $count === 0 ? 0 : max($waits),
            'emergency_wait' => $this->averageFor($served, 'emergency'),
            'senior_wait' => $this->averageFor($served, 'senior'),
            'normal_wait' => $this->averageFor($served, 'normal'),
        ];
    }

    /**
     * @param  list<array{priority: string, wait: int}>  $served
     */
    private function averageFor(array $served, string $priority): int
    {
        $waits = array_column(array_filter($served, fn (array $row): bool => $row['priority'] === $priority), 'wait');

        if ($waits === []) {
            return 0;
        }

        return (int) round(array_sum($waits) / count($waits));
    }
}
