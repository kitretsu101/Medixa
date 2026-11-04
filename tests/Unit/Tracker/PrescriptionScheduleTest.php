<?php

namespace Tests\Unit\Tracker;

use App\Services\Tracker\PrescriptionScheduleService;
use PHPUnit\Framework\TestCase;

class PrescriptionScheduleTest extends TestCase
{
    private PrescriptionScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PrescriptionScheduleService;
    }

    public function test_parses_duration_days_correctly(): void
    {
        $this->assertEquals(7, $this->service->parseDurationDays('7 days'));
        $this->assertEquals(14, $this->service->parseDurationDays('2 weeks'));
        $this->assertEquals(5, $this->service->parseDurationDays('5'));
        $this->assertEquals(10, $this->service->parseDurationDays('10 days'));
        $this->assertEquals(21, $this->service->parseDurationDays('3 wks'));
        $this->assertNull($this->service->parseDurationDays(''));
    }

    public function test_determines_max_duration_across_multiple_medicines(): void
    {
        $medicines = [
            ['name' => 'Paracetamol', 'dosage' => '500mg', 'duration' => '5 days'],
            ['name' => 'Amoxicillin', 'dosage' => '250mg', 'duration' => '10 days'],
            ['name' => 'Antacid', 'dosage' => '10ml', 'duration' => '3 days'],
        ];

        $this->assertEquals(10, $this->service->determineMaxDurationDays($medicines));
    }

    public function test_defaults_to_seven_days_when_duration_missing(): void
    {
        $medicines = [
            ['name' => 'Paracetamol', 'dosage' => '500mg'],
        ];

        $this->assertEquals(7, $this->service->determineMaxDurationDays($medicines));
    }

    public function test_determines_exact_five_day_duration(): void
    {
        $medicines = [
            ['name' => 'Paracetamol', 'dosage' => '500mg', 'duration' => '5 days'],
        ];

        $this->assertEquals(5, $this->service->determineMaxDurationDays($medicines));
    }

    public function test_parses_month_durations_with_accurate_calendar_days(): void
    {
        // November has 30 days
        $novStart = \Carbon\Carbon::parse('2026-11-01');
        $this->assertEquals(30, $this->service->parseDurationDays('1 month', $novStart));

        // December has 31 days
        $decStart = \Carbon\Carbon::parse('2026-12-01');
        $this->assertEquals(31, $this->service->parseDurationDays('1 month', $decStart));

        // 2 months starting Nov 1: Nov(30) + Dec(31) = 61 days
        $this->assertEquals(61, $this->service->parseDurationDays('2 months', $novStart));

        // 3 months starting Nov 1: Nov(30) + Dec(31) + Jan(31) = 92 days
        $this->assertEquals(92, $this->service->parseDurationDays('3 months', $novStart));
    }

    public function test_parses_three_slot_frequency_notation(): void
    {
        // 1-0-1 -> morning & night
        $this->assertEquals(['morning', 'night'], $this->service->parseFrequencySlots('1-0-1'));
        $this->assertEquals(['morning', 'night'], $this->service->parseFrequencySlots('1+0+1'));

        // 1-1-1 -> morning, afternoon, night
        $this->assertEquals(['morning', 'afternoon', 'night'], $this->service->parseFrequencySlots('1-1-1'));

        // 1-0-0 -> morning
        $this->assertEquals(['morning'], $this->service->parseFrequencySlots('1-0-0'));

        // 0-0-1 -> night
        $this->assertEquals(['night'], $this->service->parseFrequencySlots('0-0-1'));
    }

    public function test_parses_four_slot_frequency_notation(): void
    {
        // 1-1-1-1 -> morning, afternoon, evening, night
        $this->assertEquals(['morning', 'afternoon', 'evening', 'night'], $this->service->parseFrequencySlots('1-1-1-1'));
        $this->assertEquals(['morning', 'evening'], $this->service->parseFrequencySlots('1-0-1-0'));
    }

    public function test_parses_textual_frequencies(): void
    {
        $this->assertEquals(['morning', 'night'], $this->service->parseFrequencySlots('Twice daily'));
        $this->assertEquals(['morning', 'afternoon', 'night'], $this->service->parseFrequencySlots('Three times daily'));
        $this->assertEquals(['morning', 'afternoon', 'evening', 'night'], $this->service->parseFrequencySlots('Four times a day'));
        $this->assertEquals(['night'], $this->service->parseFrequencySlots('At bedtime'));
    }
}

