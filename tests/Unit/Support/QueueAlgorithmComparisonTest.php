<?php

namespace Tests\Unit\Support;

use App\Services\QueueOptimizationService;
use PHPUnit\Framework\TestCase;

class QueueAlgorithmComparisonTest extends TestCase
{
    public function test_priority_with_aging_serves_emergencies_sooner_than_fifo_and_limits_starvation(): void
    {
        $service = new QueueOptimizationService;

        $comparison = $service->compareAlgorithms();
        $byName = [];

        foreach ($comparison['algorithms'] as $row) {
            $byName[$row['name']] = $row;
        }

        $this->assertLessThan(
            $byName['FIFO']['emergency_wait'],
            $byName['Priority + aging']['emergency_wait'],
        );

        $starvation = $service->starvationSample();
        $priority = $service->simulate($starvation, 'priority');
        $aging = $service->simulate($starvation, 'priority_aging');

        $this->assertLessThan($priority['normal_wait'], $aging['normal_wait']);
        $this->assertSame(1100, $service->score('emergency', 0));
        $this->assertGreaterThan($service->score('senior', 0), $service->score('normal', 80));
    }
}
