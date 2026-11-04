<?php

namespace Tests\Feature\Tracker;

use App\Models\MedicationDose;
use App\Models\MedicationSchedule;
use App\Models\RecoveryJourney;
use App\Models\TrackerInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackerPwaTest extends TestCase
{
    public function test_pwa_manifest_file_exists_and_valid(): void
    {
        $manifestPath = public_path('manifest-tracker.json');
        $this->assertFileExists($manifestPath);

        $json = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($json);
        $this->assertEquals('Medixa Medicine Tracker', $json['name']);
        $this->assertEquals('Medixa Tracker', $json['short_name']);
        $this->assertEquals('standalone', $json['display']);
        $this->assertEquals('#168071', $json['theme_color']);
        $this->assertNotEmpty($json['icons']);
    }

    public function test_service_worker_file_exists(): void
    {
        $swPath = public_path('sw-tracker.js');
        $this->assertFileExists($swPath);

        $content = file_get_contents($swPath);
        $this->assertStringContainsString('medixa-tracker-v1', $content);
        $this->assertStringContainsString('/tracker/offline', $content);
    }

    public function test_tracker_landing_page_accessible(): void
    {
        $response = $this->get(route('tracker.app'));
        $response->assertStatus(200);
        $response->assertSee('Medixa Tracker');
        $response->assertSee('Recovery Garden');
    }

    public function test_tracker_offline_page_accessible(): void
    {
        $response = $this->get(route('tracker.offline'));
        $response->assertStatus(200);
        $response->assertSee('You are currently offline');
    }
}

