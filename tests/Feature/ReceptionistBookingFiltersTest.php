<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ReceptionistBookingFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_and_date_filters_work_on_both_request_lists_without_crossing_hospitals(): void
    {
        $hospital = Hospital::factory()->create();
        $doctor = User::factory()->doctor()->create(['hospital_id' => $hospital->id]);
        $secondDoctor = User::factory()->doctor()->create(['hospital_id' => $hospital->id]);
        $foreignDoctor = User::factory()->doctor()->create(['hospital_id' => Hospital::factory()->create()->id]);
        $first = Appointment::factory()->create(['hospital_id' => $hospital->id, 'doctor_id' => $doctor->id, 'appointment_date' => '2026-12-01']);
        Appointment::factory()->create(['hospital_id' => $hospital->id, 'doctor_id' => $secondDoctor->id, 'appointment_date' => '2026-12-01']);
        Appointment::factory()->create(['hospital_id' => $hospital->id, 'doctor_id' => $doctor->id, 'appointment_date' => '2026-12-02']);
        Appointment::factory()->create(['hospital_id' => $foreignDoctor->hospital_id, 'doctor_id' => $foreignDoctor->id, 'appointment_date' => '2026-12-01']);
        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $hospital->id]));

        foreach (['receptionist.appointments.index', 'receptionist.dashboard'] as $route) {
            $this->get(route($route, ['doctor_id' => $doctor->id, 'appointment_date' => '2026-12-01']))
                ->assertOk()->assertSee('All Doctors')->assertSee('Apply filters')
                ->assertViewHas('requests', fn ($rows): bool => $rows->count() === 1 && $rows->first()->id === $first->id)
                ->assertViewHas('doctors', fn ($rows): bool => $rows->count() === 2 && ! $rows->contains('id', $foreignDoctor->id));
        }
        $this->getJson(route('receptionist.appointments.index', ['doctor_id' => $foreignDoctor->id]))->assertUnprocessable()->assertJsonValidationErrors('doctor_id');
    }

    #[TestWith(['appointment_newest', false])]
    #[TestWith(['appointment_oldest', true])]
    #[TestWith(['doctor', false])]
    #[TestWith(['created_newest', false])]
    #[TestWith(['created_oldest', true])]
    #[TestWith(['pending', false])]
    public function test_explicit_request_sort_orders(string $sort, bool $reversed): void
    {
        $hospital = Hospital::factory()->create();
        $alpha = User::factory()->doctor()->create(['hospital_id' => $hospital->id, 'name' => 'Alpha Doctor']);
        $zulu = User::factory()->doctor()->create(['hospital_id' => $hospital->id, 'name' => 'Zulu Doctor']);
        $first = Appointment::factory()->create(['hospital_id' => $hospital->id, 'doctor_id' => $alpha->id, 'appointment_date' => '2026-12-02', 'created_at' => '2026-11-28 12:00:00']);
        $second = Appointment::factory()->create(['hospital_id' => $hospital->id, 'doctor_id' => $zulu->id, 'appointment_date' => '2026-12-01', 'created_at' => '2026-11-27 12:00:00']);
        $expected = $reversed ? [$second->id, $first->id] : [$first->id, $second->id];

        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $hospital->id]))
            ->get(route('receptionist.appointments.index', ['sort' => $sort]))->assertOk()
            ->assertViewHas('requests', fn ($rows): bool => $rows->pluck('id')->all() === $expected);
    }

    #[TestWith(['appointment_date', 'not-a-date'])]
    #[TestWith(['sort', 'invalid-sql-order'])]
    public function test_invalid_filters_are_rejected(string $field, string $value): void
    {
        $hospital = Hospital::factory()->create();
        $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $hospital->id]))
            ->getJson(route('receptionist.appointments.index', [$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public function test_pagination_retains_doctor_date_and_sort_filters(): void
    {
        $hospital = Hospital::factory()->create();
        $doctor = User::factory()->doctor()->create(['hospital_id' => $hospital->id]);
        $patient = User::factory()->patient()->create();
        Appointment::factory()->count(21)->sequence(fn ($sequence) => ['serial_number' => $sequence->index + 1])
            ->create(['hospital_id' => $hospital->id, 'doctor_id' => $doctor->id, 'patient_id' => $patient->id, 'appointment_date' => '2026-12-01']);
        $filters = ['doctor_id' => $doctor->id, 'appointment_date' => '2026-12-01', 'sort' => 'created_oldest'];
        $response = $this->actingAs(User::factory()->receptionist()->create(['hospital_id' => $hospital->id]))
            ->get(route('receptionist.appointments.index', $filters))->assertOk();
        $rows = $response->viewData('requests');
        $this->assertSame(21, $rows->total());
        parse_str(parse_url($rows->url(2), PHP_URL_QUERY), $query);
        $this->assertSame((string) $doctor->id, $query['doctor_id']);
        $this->assertSame('2026-12-01', $query['appointment_date']);
        $this->assertSame('created_oldest', $query['sort']);
        $this->get($rows->url(2))->assertOk()->assertViewHas('requests', fn ($rows): bool => $rows->count() === 1);
    }
}
