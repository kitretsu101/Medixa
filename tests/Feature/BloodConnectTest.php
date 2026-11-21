<?php

namespace Tests\Feature;

use App\Models\BloodDonationRecord;
use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Models\BloodRequestDonor;
use App\Models\User;
use App\Services\BloodMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BloodConnectTest extends TestCase
{
    use RefreshDatabase;

    private function requestData(array $overrides = []): array
    {
        return array_replace(['patient_name' => 'Private Patient', 'contact_name' => 'Private Contact', 'blood_group' => 'A+', 'units_required' => 2, 'hospital_name' => 'Medixa Hospital', 'district' => 'Dhaka', 'area' => 'Dhanmondi', 'required_date' => today()->addDay()->toDateString(), 'emergency_level' => 'urgent', 'contact_number' => '+8801712345678', 'description' => 'Private medical description', 'contact_consent' => '1'], $overrides);
    }

    private function donorData(array $overrides = []): array
    {
        return array_replace(['full_name' => 'Donor Name', 'blood_group' => 'O-', 'phone' => '01812345678', 'district' => 'Dhaka', 'area' => 'Dhanmondi', 'last_donation_date' => null, 'availability_status' => 'available', 'public_profile' => '1', 'contact_consent' => '1'], $overrides);
    }

    public function test_guest_pages_and_home_entry_are_accessible(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Medixa Blood Connect');
        foreach (['index', 'requests.index', 'requests.create', 'sent'] as $page) {
            $this->get(route('blood-connect.'.$page))->assertOk();
        }
        $this->get(route('blood-connect.donor.register'))->assertOk();
        $this->put(route('blood-connect.donor.profile'), $this->donorData())->assertRedirect(route('login'));
    }

    public function test_guest_submission_is_private_pending_and_ignores_privileged_fields(): void
    {
        $this->post(route('blood-connect.requests.store'), $this->requestData(['status' => 'active', 'units_fulfilled' => 2, 'verified_at' => now(), 'user_id' => 99]))
            ->assertRedirect(route('blood-connect.sent'))->assertSessionHas('request_code');
        $request = BloodRequest::sole();
        $this->assertSame('pending', $request->status);
        $this->assertSame(0, $request->units_fulfilled);
        $this->assertNull($request->user_id);
        $this->assertNull($request->verified_at);
        $this->assertSame('01712345678', $request->contact_number);
        $this->assertSame('submitted', $request->events()->sole()->action);
        $this->get(route('blood-connect.requests.show', $request))->assertNotFound();
        $this->get(route('blood-connect.requests.index'))->assertDontSee($request->request_code);
    }

    public function test_signed_in_request_is_linked_to_current_account(): void
    {
        $user = User::factory()->patient()->create();
        $this->actingAs($user)->post(route('blood-connect.requests.store'), $this->requestData())->assertRedirect();
        $this->assertSame($user->id, BloodRequest::sole()->user_id);
    }

    #[TestWith(['01712345678'])]
    #[TestWith(['+8801712345678'])]
    #[TestWith(['008801712345678'])]
    public function test_bangladeshi_mobile_formats_are_normalized(string $phone): void
    {
        $this->post(route('blood-connect.requests.store'), $this->requestData(['contact_number' => $phone]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('01712345678', BloodRequest::sole()->contact_number);
    }

    #[DataProvider('invalidRequests')]
    public function test_invalid_public_submission_is_rejected(string $field, mixed $value): void
    {
        $this->post(route('blood-connect.requests.store'), $this->requestData([$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('blood_requests', 0);
    }

    public static function invalidRequests(): array
    {
        return ['missing patient' => ['patient_name', ''], 'missing contact' => ['contact_name', ''], 'invalid group' => ['blood_group', 'C+'], 'zero units' => ['units_required', 0], 'excess units' => ['units_required', 21], 'fractional units' => ['units_required', 1.5], 'missing hospital' => ['hospital_name', ''], 'missing district' => ['district', ''], 'missing area' => ['area', ''], 'past date' => ['required_date', '2020-01-01'], 'invalid date' => ['required_date', 'not-a-date'], 'missing phone' => ['contact_number', ''], 'invalid phone' => ['contact_number', '01212345678'], 'foreign phone' => ['contact_number', '+15555555555'], 'invalid urgency' => ['emergency_level', 'immediate'], 'invalid consent' => ['contact_consent', 'yes'], 'spam honeypot' => ['website', 'https://spam.example'], 'oversized description' => ['description', str_repeat('x', 2001)]];
    }

    public function test_request_more_than_thirty_days_ahead_is_rejected(): void
    {
        $this->post(route('blood-connect.requests.store'), $this->requestData(['required_date' => today()->addDays(31)->toDateString()]))->assertSessionHasErrors('required_date');
    }

    public function test_duplicate_submission_has_validation_error_and_unique_code(): void
    {
        $this->post(route('blood-connect.requests.store'), $this->requestData())->assertRedirect();
        $this->post(route('blood-connect.requests.store'), $this->requestData(['contact_number' => '01712345678']))->assertSessionHasErrors('contact_number');
        $this->post(route('blood-connect.requests.store'), $this->requestData(['hospital_name' => 'Another hospital']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('blood_requests', 2);
        $this->assertSame(2, BloodRequest::distinct()->count('request_code'));
    }

    public function test_public_listing_only_exposes_active_approved_unfulfilled_unexpired_requests(): void
    {
        $active = BloodRequest::factory()->active()->create();
        $hidden = collect([
            BloodRequest::factory()->create(),
            BloodRequest::factory()->active()->create(['verified_at' => null]),
            BloodRequest::factory()->active()->create(['status' => 'rejected']),
            BloodRequest::factory()->active()->create(['expires_at' => now()->subSecond()]),
            BloodRequest::factory()->active()->create(['required_date' => today()->subDay()]),
            BloodRequest::factory()->active()->create(['units_fulfilled' => 2]),
            BloodRequest::factory()->active()->create(['fulfilled_at' => now()]),
        ]);
        $response = $this->get(route('blood-connect.requests.index'))->assertSee($active->request_code);
        foreach ($hidden as $request) {
            $response->assertDontSee($request->request_code);
            $this->get(route('blood-connect.requests.show', $request))->assertNotFound();
            $this->assertFalse($request->isActive());
        }
    }

    public function test_public_details_keep_patient_description_contact_and_account_private_without_consent(): void
    {
        $request = BloodRequest::factory()->active()->create(['patient_name' => 'SecretPatient', 'contact_name' => 'SecretContact', 'description' => 'SecretDiagnosis', 'contact_number' => '01987654321', 'contact_consent' => false]);
        $this->get(route('blood-connect.requests.show', $request))->assertOk()->assertDontSee('SecretPatient')->assertDontSee('SecretContact')->assertDontSee('SecretDiagnosis')->assertDontSee('01987654321');
        $request->update(['contact_consent' => true]);
        $this->get(route('blood-connect.requests.show', $request))->assertSee('tel:01987654321', false)->assertDontSee('SecretPatient');
        $this->assertArrayNotHasKey('patient_name', $request->toArray());
        $this->assertArrayNotHasKey('contact_number', $request->toArray());
        $this->assertArrayNotHasKey('user_id', $request->toArray());
    }

    public function test_search_filters_and_sorting_are_applied_with_pagination(): void
    {
        $match = BloodRequest::factory()->active()->create(['hospital_name' => 'Target Hospital', 'district' => 'Dhaka', 'area' => 'North', 'blood_group' => 'O+', 'emergency_level' => 'critical']);
        BloodRequest::factory()->active()->create(['hospital_name' => 'Wrong Hospital', 'district' => 'Chattogram', 'blood_group' => 'A-']);
        $this->get(route('blood-connect.requests.index', ['blood_group' => 'O+', 'district' => 'Dhaka', 'area' => 'North', 'hospital' => 'Target', 'emergency_level' => 'critical', 'required_date' => $match->required_date->toDateString(), 'q' => 'Target', 'sort' => 'priority']))
            ->assertViewHas('requests', fn ($requests) => $requests->total() === 1 && $requests->first()->id === $match->id);
        $this->get(route('blood-connect.requests.index', ['q' => "x' OR 1=1 --"]))->assertViewHas('requests', fn ($requests) => $requests->total() === 0);
        $this->get(route('blood-connect.requests.index', ['sort' => 'id desc; drop table users']))->assertSessionHasErrors('sort');
        $soon = BloodRequest::factory()->active()->create(['required_date' => today()]);
        $this->get(route('blood-connect.requests.index', ['sort' => 'needed_soon']))->assertViewHas('requests', fn ($requests) => $requests->first()->id === $soon->id);
        BloodRequest::factory()->active()->count(13)->create();
        $this->get(route('blood-connect.requests.index'))->assertViewHas('requests', fn ($requests) => $requests->count() === 12 && $requests->hasMorePages());
    }

    public function test_public_free_text_is_html_escaped(): void
    {
        $request = BloodRequest::factory()->active()->create(['hospital_name' => '<script>alert(1)</script>', 'area' => '<img src=x onerror=alert(2)>']);
        $this->get(route('blood-connect.requests.show', $request))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x onerror=alert(2)>', false);
    }

    public function test_legacy_donor_update_availability_and_history_remain_account_scoped(): void
    {
        $user = User::factory()->patient()->create();
        $other = BloodDonor::factory()->create();
        $donor = BloodDonor::factory()->create(['user_id' => $user->id]);
        $this->assertNull($donor->phone_verified_at);
        $this->actingAs($user)->get(route('blood-connect.donor.profile'))->assertOk()->assertDontSee($other->full_name);
        $this->put(route('blood-connect.donor.update'), $this->donorData(['full_name' => 'Updated', 'user_id' => $other->user_id, 'id' => $other->id]))->assertRedirect();
        $this->assertSame('Updated', $donor->fresh()->full_name);
        $this->assertSame($other->full_name, $other->fresh()->full_name);
        $this->put(route('blood-connect.donor.availability'), ['availability_status' => 'unavailable'])->assertRedirect();
        $this->assertSame('unavailable', $donor->fresh()->availability_status);
        $this->assertFalse(Gate::forUser($other->user)->allows('update', $donor));
        $this->assertTrue(Gate::forUser($user)->allows('update', $donor));
        BloodDonationRecord::factory()->create(['blood_donor_id' => $donor->id, 'hospital_name' => 'My history hospital']);
        $this->get(route('blood-connect.donor.profile'))->assertSee('My history hospital');
    }

    public function test_donor_validation_rejects_future_donation_invalid_phone_and_privileged_attributes(): void
    {
        $this->actingAs(User::factory()->create())->post(route('blood-connect.donor.store'), $this->donorData(['last_donation_date' => today()->addDay()->toDateString(), 'phone' => '01111111111', 'blood_group' => 'C+', 'availability_status' => 'online']))->assertSessionHasErrors(['last_donation_date', 'phone', 'blood_group', 'availability_status']);
        $this->assertDatabaseCount('blood_donors', 0);
    }

    #[TestWith(['available', true, true, true])]
    #[TestWith(['temporarily_unavailable', true, true, false])]
    #[TestWith(['unavailable', true, true, false])]
    #[TestWith(['available', false, true, false])]
    #[TestWith(['available', true, false, false])]
    public function test_matching_respects_availability_and_both_consents(string $status, bool $public, bool $contact, bool $expected): void
    {
        $request = BloodRequest::factory()->active()->create();
        $donor = BloodDonor::factory()->create(['availability_status' => $status, 'public_profile' => $public, 'contact_consent' => $contact]);
        $this->assertSame($expected, $donor->isDiscoverable());
        $this->assertSame($expected, app(BloodMatchingService::class)->matches($request)->whereKey($donor->id)->exists());
    }

    public function test_matching_excludes_wrong_group_and_district_and_prioritizes_same_area(): void
    {
        $request = BloodRequest::factory()->active()->create(['blood_group' => 'A-']);
        BloodDonor::factory()->create(['blood_group' => 'A+']);
        BloodDonor::factory()->create(['district' => 'Chattogram']);
        $far = BloodDonor::factory()->create(['area' => 'Elsewhere']);
        $near = BloodDonor::factory()->create();
        $this->assertSame([$near->id, $far->id], app(BloodMatchingService::class)->matches($request)->pluck('id')->all());
    }

    public function test_interest_is_unique_and_does_not_fulfill_request(): void
    {
        $request = BloodRequest::factory()->active()->create();
        $donor = BloodDonor::factory()->create();
        $this->actingAs($donor->user)->post(route('blood-connect.requests.interest', $request))->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('blood-connect.requests.interest', $request))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('blood_request_donors', 1);
        $this->assertSame('active', $request->fresh()->status);
        $this->assertSame(0, $request->fresh()->units_fulfilled);
        $donor->update(['availability_status' => 'unavailable']);
        $this->post(route('blood-connect.requests.interest', $request))->assertSessionHasErrors('donor');
        $request->update(['expires_at' => now()->subSecond()]);
        $donor->update(['availability_status' => 'available']);
        $this->post(route('blood-connect.requests.interest', $request))->assertNotFound();
    }

    public function test_incompatible_donor_cannot_express_interest(): void
    {
        $request = BloodRequest::factory()->active()->create(['blood_group' => 'O-']);
        $donor = BloodDonor::factory()->create(['blood_group' => 'AB+']);
        $this->actingAs($donor->user)->post(route('blood-connect.requests.interest', $request))->assertSessionHasErrors('donor');
        $this->assertDatabaseCount('blood_request_donors', 0);
    }

    #[TestWith(['patient'])]
    #[TestWith(['doctor'])]
    #[TestWith(['receptionist'])]
    #[TestWith(['lab_assistant'])]
    public function test_all_non_admin_roles_are_denied_admin_module_operations(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $request = BloodRequest::factory()->create();
        $donor = BloodDonor::factory()->create();
        $this->actingAs($user);
        foreach (['index', 'requests.index', 'donors.index', 'donations.index'] as $page) {
            $this->get(route('admin.blood-connect.'.$page))->assertForbidden();
        }
        $this->get(route('admin.blood-connect.requests.show', $request))->assertForbidden();
        foreach (['approve', 'reject', 'fulfill'] as $action) {
            $this->post(route('admin.blood-connect.requests.'.$action, $request), ['notes' => 'A valid review'])->assertForbidden();
        }
        $this->put(route('admin.blood-connect.requests.update', $request), ['status' => 'active', 'notes' => 'A valid review'])->assertForbidden();
        $this->put('/admin/blood-connect/donors/'.$donor->id, ['availability_status' => 'unavailable'])->assertNotFound();
        $this->post(route('admin.blood-connect.donations.store', $request), [])->assertForbidden();
        $this->assertSame('pending', $request->fresh()->status);
        $this->assertDatabaseCount('blood_donation_records', 0);
    }

    public function test_admin_can_approve_pending_request_with_private_history(): void
    {
        $admin = User::factory()->admin()->create();
        $request = BloodRequest::factory()->create();
        $this->actingAs($admin)->post(route('admin.blood-connect.requests.approve', $request), ['notes' => 'Verified with treating hospital'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('active', $request->fresh()->status);
        $this->assertNotNull($request->fresh()->verified_at);
        $this->assertDatabaseHas('blood_request_events', ['blood_request_id' => $request->id, 'user_id' => $admin->id, 'action' => 'active', 'notes' => 'Verified with treating hospital']);
        foreach (['index', 'requests.index', 'donors.index', 'donations.index'] as $page) {
            $this->get(route('admin.blood-connect.'.$page))->assertOk();
        }
        $this->get(route('admin.blood-connect.requests.show', $request))->assertOk()->assertSee($request->patient_name);
    }

    public function test_invalid_transitions_expired_approvals_and_unverified_fulfillment_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $request = BloodRequest::factory()->create(['expires_at' => now()->subSecond()]);
        $this->actingAs($admin)->post(route('admin.blood-connect.requests.approve', $request), ['notes' => 'Checked hospital'])->assertSessionHasErrors('status');
        $active = BloodRequest::factory()->active()->create();
        $this->post(route('admin.blood-connect.requests.fulfill', $active), ['notes' => 'Not yet donated'])->assertSessionHasErrors('status');
        $this->put(route('admin.blood-connect.requests.update', $active), ['status' => 'pending', 'notes' => 'Invalid transition'])->assertSessionHasErrors('status');
        $this->post(route('admin.blood-connect.requests.reject', $active), ['notes' => 'Reported duplicate'])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('admin.blood-connect.requests.approve', $active), ['notes' => 'Cannot reopen rejected'])->assertSessionHasErrors('status');
        $this->assertSame('rejected', $active->fresh()->status);
    }

    public function test_verified_donations_increment_units_and_fulfill_only_at_target(): void
    {
        $admin = User::factory()->admin()->create();
        $request = BloodRequest::factory()->active()->create();
        $donors = BloodDonor::factory()->count(2)->create();
        foreach ($donors as $index => $donor) {
            BloodRequestDonor::factory()->create(['blood_request_id' => $request->id, 'blood_donor_id' => $donor->id]);
            $this->actingAs($admin)->post(route('admin.blood-connect.donations.store', $request), ['blood_donor_id' => $donor->id, 'donation_date' => today()->toDateString(), 'units_donated' => 1, 'verified_by' => 999, 'status' => 'pending'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($index + 1, $request->fresh()->units_fulfilled);
            $this->assertSame($index === 0 ? 'active' : 'fulfilled', $request->fresh()->status);
            $this->assertSame('temporarily_unavailable', $donor->fresh()->availability_status);
            $this->assertSame(today()->toDateString(), $donor->fresh()->last_donation_date->toDateString());
        }
        $this->assertNotNull($request->fresh()->fulfilled_at);
        $this->assertDatabaseCount('blood_donation_records', 2);
        $this->assertDatabaseHas('blood_donation_records', ['verified_by' => $admin->id, 'status' => 'verified']);
        $this->get(route('blood-connect.requests.show', $request))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.blood-connect.donations.index'))->assertOk()->assertSee($donors->first()->full_name);
    }

    public function test_uninterested_overcount_future_and_duplicate_donations_are_rejected_without_side_effects(): void
    {
        $request = BloodRequest::factory()->active()->create(['units_required' => 3]);
        $donor = BloodDonor::factory()->create();
        $payload = ['blood_donor_id' => $donor->id, 'donation_date' => today()->toDateString(), 'units_donated' => 1];
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.blood-connect.donations.store', $request), $payload)->assertSessionHasErrors('units_donated');
        BloodRequestDonor::factory()->create(['blood_request_id' => $request->id, 'blood_donor_id' => $donor->id]);
        $this->post(route('admin.blood-connect.donations.store', $request), array_replace($payload, ['units_donated' => 4]))->assertSessionHasErrors('units_donated');
        $this->post(route('admin.blood-connect.donations.store', $request), array_replace($payload, ['donation_date' => today()->addDay()->toDateString()]))->assertSessionHasErrors('donation_date');
        $this->post(route('admin.blood-connect.donations.store', $request), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('admin.blood-connect.donations.store', $request), $payload)->assertSessionHasErrors('donation_date');
        $this->assertDatabaseCount('blood_donation_records', 1);
        $this->assertSame(1, $request->fresh()->units_fulfilled);
    }

    public function test_guest_report_is_private_and_notifies_only_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();
        $request = BloodRequest::factory()->active()->create();
        $this->from(route('blood-connect.requests.show', $request))->post(route('blood-connect.requests.report', $request), ['reason' => 'Suspicious private report text'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(0, $patient->notifications()->count());
        $this->assertDatabaseHas('blood_request_events', ['action' => 'reported', 'notes' => 'Suspicious private report text']);
        $this->get(route('blood-connect.requests.show', $request))->assertDontSee('Suspicious private report text');
        $this->actingAs($admin)->get(route('admin.blood-connect.requests.show', $request))->assertSee('Suspicious private report text');
    }

    public function test_post_rate_limit_accepts_ten_attempts_then_returns_429(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson(route('blood-connect.requests.store'), [])->assertUnprocessable();
        }
        $this->postJson(route('blood-connect.requests.store'), [])->assertTooManyRequests();
        $this->postJson(route('emergency.store'), [])->assertUnprocessable();
        $this->assertDatabaseCount('blood_requests', 0);
    }

    public function test_expiration_at_the_current_instant_blocks_details_and_interest(): void
    {
        $this->freezeTime();
        $request = BloodRequest::factory()->active()->create(['expires_at' => now()]);
        $donor = BloodDonor::factory()->create();
        $this->assertFalse($request->isActive());
        $this->get(route('blood-connect.requests.show', $request))->assertNotFound();
        $this->get(route('blood-connect.requests.index'))->assertDontSee($request->request_code);
        $this->actingAs($donor->user)->post(route('blood-connect.requests.interest', $request))->assertNotFound();
        $this->assertDatabaseCount('blood_request_donors', 0);
    }

    public function test_guest_admin_access_redirects_to_login(): void
    {
        $this->get(route('admin.blood-connect.index'))->assertRedirect(route('login'));
        $request = BloodRequest::factory()->create();
        $this->post(route('admin.blood-connect.requests.approve', $request), ['notes' => 'A valid review'])->assertRedirect(route('login'));
        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_admin_donor_list_is_read_only_without_changing_donor_consent(): void
    {
        $donor = BloodDonor::factory()->create(['public_profile' => false, 'contact_consent' => false]);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.blood-connect.donors.index'))->assertOk()->assertDontSee('Update availability')->assertDontSee($donor->phone);
        $this->put('/admin/blood-connect/donors/'.$donor->id, ['availability_status' => 'unavailable', 'public_profile' => true, 'contact_consent' => true])->assertNotFound();
        $this->assertSame('available', $donor->fresh()->availability_status);
        $this->assertFalse($donor->fresh()->public_profile);
        $this->assertFalse($donor->fresh()->contact_consent);
    }

    public function test_existing_account_deletion_is_not_blocked_by_donation_records(): void
    {
        $donor = BloodDonor::factory()->create();
        $record = BloodDonationRecord::factory()->create(['blood_donor_id' => $donor->id]);
        $this->actingAs($donor->user)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');
        $this->assertDatabaseMissing('blood_donors', ['id' => $donor->id]);
        $this->assertNull($record->fresh()->blood_donor_id);
    }
}
