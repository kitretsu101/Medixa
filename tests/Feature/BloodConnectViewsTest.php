<?php

namespace Tests\Feature;

use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BloodConnectViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_and_request_forms_keep_public_privacy_and_form_actions(): void
    {
        $donor = BloodDonor::factory()->independent()->phoneVerified()->create(['full_name' => 'Review Donor']);
        BloodRequest::factory()->active()->create(['hospital_name' => 'Review Hospital', 'emergency_level' => 'urgent']);
        $directory = $this->get(route('blood-connect.donors.index'))
            ->assertOk()->assertSee('Available')->assertSee('Review Donor')->assertDontSee($donor->phone);
        $this->saveReview('directory', $directory);
        $form = $this->get(route('blood-connect.requests.create'))->assertOk()
            ->assertSee('action="'.route('blood-connect.requests.store').'"', false)->assertSee('name="contact_consent"', false);
        $this->saveReview('request-form', $form);
        $contact = $this->get(route('blood-connect.donors.request', $donor))->assertOk()->assertDontSee($donor->phone)
            ->assertSee('action="'.route('blood-connect.donors.request.store', $donor).'"', false);
        $this->saveReview('contact-form', $contact);
        $requests = $this->get(route('blood-connect.requests.index'))->assertOk()->assertSee('Urgent');
        $this->saveReview('requests', $requests);
    }

    public function test_donor_dashboard_preserves_preferences_and_admin_views_show_real_statuses(): void
    {
        $donor = BloodDonor::factory()->independent()->phoneVerified()->create(['full_name' => 'Review Donor']);
        $dashboard = $this->actingAs($donor, 'donor')->get(route('blood-connect.donor.dashboard'))
            ->assertOk()->assertSee('Directory preferences')->assertSee('name="public_profile"', false)
            ->assertSee('action="'.route('blood-connect.donor.preferences').'"', false);
        $this->saveReview('dashboard', $dashboard);

        $this->actingAs(User::factory()->create(['role' => 'admin']), 'web');
        $donors = $this->get(route('admin.blood-connect.donors.index'))
            ->assertOk()->assertSee('Review Donor')->assertDontSee($donor->phone);
        $this->saveReview('admin-donors', $donors);
        BloodRequest::factory()->create(['status' => 'fulfilled', 'units_fulfilled' => 2]);
        $requests = $this->get(route('admin.blood-connect.requests.index'))->assertOk()->assertSee('Fulfilled');
        $this->saveReview('admin-requests', $requests);
    }

    private function saveReview(string $name, TestResponse $response): void
    {
        if ($directory = getenv('MEDIXA_UI_REVIEW_DIRECTORY')) {
            file_put_contents($directory.DIRECTORY_SEPARATOR.$name.'.html', $response->getContent());
        }
    }
}
