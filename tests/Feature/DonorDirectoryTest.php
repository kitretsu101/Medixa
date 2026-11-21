<?php

namespace Tests\Feature;

use App\Models\BloodDonor;
use App\Models\DonorContactRequest;
use App\Models\User;
use App\Support\BangladeshDistricts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DonorDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function contact(array $overrides = []): array
    {
        return array_replace(['requester_name' => 'Private requester', 'requester_phone' => '+8801912345678', 'blood_group_needed' => 'A+', 'units_needed' => 2, 'hospital_name' => 'Target Hospital', 'district' => 'Dhaka', 'area' => 'Dhanmondi', 'required_date' => today()->addDay()->toDateString(), 'emergency_level' => 'urgent', 'message' => 'Private requester message'], $overrides);
    }

    public function test_directory_filters_verified_available_consenting_donors_and_never_exposes_phone(): void
    {
        $visible = BloodDonor::factory()->independent()->phoneVerified()->create(['full_name' => 'Visible Donor', 'blood_group' => 'A+', 'area' => 'North']);
        $hidden = [
            BloodDonor::factory()->independent()->create(['full_name' => 'Unverified Donor']),
            BloodDonor::factory()->independent()->phoneVerified()->create(['full_name' => 'Hidden Donor', 'public_profile' => false]),
            BloodDonor::factory()->independent()->phoneVerified()->create(['full_name' => 'Unavailable Donor', 'availability_status' => 'unavailable']),
        ];
        $response = $this->get('/blood-connect/donors')->assertOk()->assertSee('Visible Donor')->assertDontSee($visible->phone)->assertDontSee('tel:', false);
        foreach ($hidden as $donor) {
            $response->assertDontSee($donor->full_name)->assertDontSee($donor->phone);
            $this->get('/blood-connect/donors/'.$donor->id.'/request')->assertNotFound();
        }
        $this->get('/blood-connect/donors?blood_group=A%2B&district=Dhaka&area=North&availability_status=available')->assertViewHas('donors', fn ($donors): bool => $donors->total() === 1 && $donors->first()->id === $visible->id);
        $this->get('/blood-connect/donors?availability_status=unavailable')->assertViewHas('donors', fn ($donors): bool => $donors->total() === 0);
        $this->get('/blood-connect/donors?district=NotReal')->assertSessionHasErrors('district');
        $this->assertCount(64, BangladeshDistricts::ALL);
        foreach (BangladeshDistricts::ALL as $district) {
            $response->assertSee($district);
        }
        $this->get('/blood-connect/donors/'.$visible->id.'/request')->assertOk()->assertDontSee($visible->phone);
        $this->assertArrayNotHasKey('phone', $visible->toArray());
        $this->assertArrayNotHasKey('password', $visible->toArray());
    }

    public function test_public_request_creates_private_row_ignores_privileged_fields_and_reveals_no_donor_phone(): void
    {
        $donor = BloodDonor::factory()->independent()->phoneVerified()->create();
        $other = BloodDonor::factory()->independent()->phoneVerified()->create();
        $this->post('/blood-connect/donors/'.$donor->id.'/request', $this->contact(['blood_donor_id' => $other->id, 'status' => 'closed']))->assertRedirect('/blood-connect/donors/sent')->assertSessionHasNoErrors();
        $contact = DonorContactRequest::sole();
        $this->assertSame($donor->id, $contact->blood_donor_id);
        $this->assertSame('01912345678', $contact->requester_phone);
        $this->assertSame('pending', $contact->status);
        $this->assertSame('Dhaka · Dhanmondi', $contact->location);
        $this->assertDatabaseCount('blood_requests', 0);
        $this->assertDatabaseCount('blood_request_donors', 0);
        foreach (['/blood-connect/donors', '/blood-connect/donors/'.$donor->id.'/request', '/blood-connect/donors/sent'] as $url) {
            $this->get($url)->assertDontSee($donor->phone)->assertDontSee($contact->requester_phone)->assertDontSee('Private requester message');
        }
        $this->assertArrayNotHasKey('requester_phone', $contact->toArray());
        $this->actingAs(User::factory()->admin()->create(), 'web')->get('/admin/blood-connect/donors')->assertDontSee($contact->requester_phone)->assertDontSee('Private requester message');
    }

    #[TestWith(['requester_phone', ''])]
    #[TestWith(['requester_phone', '01212345678'])]
    #[TestWith(['requester_phone', '+15555555555'])]
    #[TestWith(['blood_group_needed', 'C+'])]
    #[TestWith(['units_needed', 0])]
    #[TestWith(['units_needed', 1.5])]
    #[TestWith(['units_needed', 21])]
    #[TestWith(['hospital_name', ''])]
    #[TestWith(['district', 'Invalid'])]
    #[TestWith(['area', ''])]
    #[TestWith(['required_date', '2020-01-01'])]
    #[TestWith(['emergency_level', 'immediate'])]
    public function test_invalid_contact_input_is_rejected_without_writing(string $field, mixed $value): void
    {
        $donor = BloodDonor::factory()->independent()->phoneVerified()->create();
        $this->post('/blood-connect/donors/'.$donor->id.'/request', $this->contact([$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('donor_contact_requests', 0);
    }

    public function test_same_phone_same_donor_has_rolling_twenty_four_hour_cooldown(): void
    {
        $this->freezeTime();
        $donor = BloodDonor::factory()->independent()->phoneVerified()->create();
        $other = BloodDonor::factory()->independent()->phoneVerified()->create();
        $url = '/blood-connect/donors/'.$donor->id.'/request';
        $this->post($url, $this->contact())->assertSessionHasNoErrors();
        $this->travel(23)->hours();
        $this->post($url, $this->contact(['requester_phone' => '01912345678']))->assertSessionHasErrors('requester_phone');
        $this->post('/blood-connect/donors/'.$other->id.'/request', $this->contact())->assertSessionHasNoErrors();
        $this->post($url, $this->contact(['requester_phone' => '01711111111']))->assertSessionHasNoErrors();
        $this->travel(1)->hours();
        $this->post($url, $this->contact())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('donor_contact_requests', 4);
    }

    public function test_public_contact_post_is_limited_to_five_per_minute_separately_from_blood_submission(): void
    {
        $donor = BloodDonor::factory()->independent()->phoneVerified()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/blood-connect/donors/'.$donor->id.'/request', [])->assertUnprocessable();
        }
        $this->postJson('/blood-connect/donors/'.$donor->id.'/request', $this->contact())->assertTooManyRequests();
        $this->postJson('/blood-connect/requests', [])->assertUnprocessable();
        $this->assertDatabaseCount('donor_contact_requests', 0);
    }

    public function test_inbox_and_status_updates_are_owner_scoped_and_only_allow_forward_transitions(): void
    {
        $donor = BloodDonor::factory()->independent()->phoneVerified()->create();
        $mine = DonorContactRequest::factory()->create(['blood_donor_id' => $donor->id, 'requester_phone' => '01711111111']);
        $other = DonorContactRequest::factory()->create(['requester_phone' => '01999999999', 'message' => 'Other donor private message']);
        $this->actingAs($donor, 'donor')->get('/blood-connect/donor/requests')->assertSee('01711111111')->assertDontSee('01999999999')->assertDontSee('Other donor private message');
        $this->post('/blood-connect/donor/requests/'.$other->id.'/status', ['status' => 'contacted'])->assertNotFound();
        $url = '/blood-connect/donor/requests/'.$mine->id.'/status';
        $this->post($url, ['status' => 'closed'])->assertSessionHasErrors('status');
        $this->post($url, ['status' => 'contacted'])->assertSessionHasNoErrors();
        $this->assertSame('contacted', $mine->fresh()->status);
        $this->get('/blood-connect/donor/requests?status=pending')->assertDontSee('01711111111');
        $this->get('/blood-connect/donor/requests?status=contacted')->assertSee('01711111111');
        $this->post($url, ['status' => 'pending'])->assertSessionHasErrors('status');
        $this->post($url, ['status' => 'closed'])->assertSessionHasNoErrors();
        $this->post($url, ['status' => 'contacted'])->assertSessionHasErrors('status');
        $this->assertSame('closed', $mine->fresh()->status);
        $this->assertSame('pending', $other->fresh()->status);
    }

    #[TestWith(['patient'])]
    #[TestWith(['doctor'])]
    #[TestWith(['admin'])]
    #[TestWith(['receptionist'])]
    #[TestWith(['lab_assistant'])]
    public function test_hospital_roles_cannot_read_or_modify_private_donor_requests(string $role): void
    {
        $contact = DonorContactRequest::factory()->create();
        $this->actingAs(User::factory()->create(['role' => $role]), 'web')->get('/blood-connect/donor/requests')->assertRedirect('/blood-connect/donor/login');
        $this->post('/blood-connect/donor/requests/'.$contact->id.'/status', ['status' => 'contacted'])->assertRedirect('/blood-connect/donor/login');
        $this->assertSame('pending', $contact->fresh()->status);
    }

    public function test_donor_and_requester_text_is_html_escaped(): void
    {
        $donor = BloodDonor::factory()->independent()->phoneVerified()->create(['full_name' => '<script>alert(1)</script>']);
        $this->get('/blood-connect/donors')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        DonorContactRequest::factory()->create(['blood_donor_id' => $donor->id, 'message' => '<script>alert(2)</script>']);
        $this->actingAs($donor, 'donor')->get('/blood-connect/donor/requests')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(2)</script>', false);
    }

    public function test_admin_can_only_view_donors_and_no_donor_mutation_route_exists(): void
    {
        $donor = BloodDonor::factory()->independent()->phoneVerified()->create();
        $this->actingAs(User::factory()->admin()->create(), 'web')->get('/admin/blood-connect/donors')->assertOk()->assertSee('Phone verified')->assertDontSee('Update availability')->assertDontSee($donor->phone);
        foreach (['approve', 'reject', 'verify'] as $action) {
            $this->post('/admin/blood-connect/donors/'.$donor->id.'/'.$action)->assertNotFound();
        }
        $this->put('/admin/blood-connect/donors/'.$donor->id, ['availability_status' => 'unavailable'])->assertNotFound();
        $this->assertFalse(Route::has('admin.blood-connect.donors.update'));
        $this->assertSame('available', $donor->fresh()->availability_status);
    }
}
