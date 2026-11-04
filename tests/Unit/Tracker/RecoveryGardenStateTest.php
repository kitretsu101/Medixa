<?php

namespace Tests\Unit\Tracker;

use App\Models\RecoveryJourney;
use App\Services\Tracker\RecoveryJourneyService;
use PHPUnit\Framework\TestCase;

class RecoveryGardenStateTest extends TestCase
{
    private RecoveryJourneyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecoveryJourneyService;
    }

    public function test_state_constants_defined(): void
    {
        $this->assertEquals('S0_SEED', RecoveryJourney::STATE_SEED);
        $this->assertEquals('S1_SPROUT', RecoveryJourney::STATE_SPROUT);
        $this->assertEquals('S2_GROWING', RecoveryJourney::STATE_GROWING);
        $this->assertEquals('S3_BLOOM', RecoveryJourney::STATE_BLOOM);
        $this->assertEquals('W_WILTED', RecoveryJourney::STATE_WILTED);
    }

    public function test_journey_is_wilted_method(): void
    {
        $journey = new RecoveryJourney;
        $journey->current_state = RecoveryJourney::STATE_WILTED;
        $this->assertTrue($journey->isWilted());

        $journey->current_state = RecoveryJourney::STATE_SPROUT;
        $this->assertFalse($journey->isWilted());
    }

    public function test_journey_is_completed_method(): void
    {
        $journey = new RecoveryJourney;
        $journey->status = 'completed';
        $this->assertTrue($journey->isCompleted());

        $journey->status = 'active';
        $this->assertFalse($journey->isCompleted());
    }
}

