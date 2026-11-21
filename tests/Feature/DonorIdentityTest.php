<?php

namespace Tests\Feature;

use App\Http\Middleware\StartDonorSession;
use App\Models\BloodDonor;
use App\Models\DonorOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DonorIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $overrides = []): array
    {
        return array_replace(['full_name' => 'Independent Donor', 'blood_group' => 'A+', 'phone' => '+8801712345678', 'district' => 'Dhaka', 'area' => 'Dhanmondi', 'last_donation_date' => null, 'password' => 'donor-password', 'password_confirmation' => 'donor-password', 'availability_status' => 'available', 'public_profile' => '1', 'contact_consent' => '0'], $overrides);
    }

    public function test_registration_creates_unrelated_unverified_account_and_hashed_otp(): void
    {
        $this->get('/blood-connect/donor/register')->assertOk()->assertDontSee(route('login'), false);
        $this->post('/blood-connect/donor/register', $this->registration(['user_id' => 999, 'phone_verified_at' => now()]))->assertRedirect('/blood-connect/donor/dashboard')->assertSessionHasNoErrors();
        $donor = BloodDonor::sole();
        $this->assertNull($donor->user_id);
        $this->assertNull($donor->phone_verified_at);
        $this->assertSame('01712345678', $donor->phone);
        $this->assertTrue(Hash::check('donor-password', $donor->password));
        $this->assertDatabaseCount('users', 0);
        $this->assertAuthenticatedAs($donor, 'donor');
        $this->assertGuest('web');
        $otp = DonorOtp::sole();
        $code = session('dev_otp');
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
        $this->assertTrue(Hash::check($donor->phone.'|'.$code, $otp->code_hash));
        $this->assertTrue($otp->expires_at->isFuture());
        $this->get('/blood-connect/donors')->assertDontSee('Independent Donor');
        $this->get('/blood-connect/donors/'.$donor->id.'/request')->assertNotFound();
        $this->post('/blood-connect/donors/'.$donor->id.'/request', [])->assertForbidden();
        $this->assertDatabaseCount('donor_contact_requests', 0);
    }

    public function test_correct_otp_verifies_once_and_makes_consenting_donor_public(): void
    {
        $this->freezeTime();
        $donor = BloodDonor::factory()->independent()->create();
        DonorOtp::factory()->create(['donor_id' => $donor->id]);
        $this->actingAs($donor, 'donor')->post('/blood-connect/donor/verify-phone', ['code' => '123456'])->assertRedirect('/blood-connect/donor/dashboard')->assertSessionHasNoErrors();
        $this->assertNotNull($donor->fresh()->phone_verified_at);
        $this->assertDatabaseCount('donor_otps', 0);
        $this->get('/blood-connect/donors')->assertSee($donor->full_name)->assertDontSee($donor->phone);
    }

    public function test_wrong_expired_and_replaced_codes_fail_without_verifying(): void
    {
        $this->freezeTime();
        $donor = BloodDonor::factory()->independent()->create();
        $otp = DonorOtp::factory()->create(['donor_id' => $donor->id]);
        $this->actingAs($donor, 'donor')->post('/blood-connect/donor/verify-phone', ['code' => '999999'])->assertSessionHasErrors('code');
        $this->assertSame(1, $otp->fresh()->attempts);
        $this->assertNull($donor->fresh()->phone_verified_at);
        $otp->update(['expires_at' => now()]);
        $this->post('/blood-connect/donor/verify-phone', ['code' => '123456'])->assertSessionHasErrors('code');
        $otp->update(['code_hash' => Hash::make('000000')]);
        $this->travel(1)->minutes();
        $this->post('/blood-connect/donor/resend-otp')->assertRedirect()->assertSessionHas('dev_otp');
        $this->assertSame(0, $otp->fresh()->attempts);
        $this->assertFalse(Hash::check('000000', $otp->fresh()->code_hash));
        $this->post('/blood-connect/donor/verify-phone', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertNull($donor->fresh()->phone_verified_at);
    }

    public function test_five_verification_attempts_per_fifteen_minutes_and_one_resend_per_minute(): void
    {
        $this->freezeTime();
        $donor = BloodDonor::factory()->independent()->create();
        DonorOtp::factory()->create(['donor_id' => $donor->id]);
        $this->actingAs($donor, 'donor');
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/blood-connect/donor/verify-phone', ['code' => '999999'])->assertUnprocessable();
        }
        $this->postJson('/blood-connect/donor/verify-phone', ['code' => '123456'])->assertTooManyRequests();
        $this->assertSame(5, DonorOtp::sole()->attempts);
        $this->post('/blood-connect/donor/resend-otp')->assertSessionHasErrors('code');
        $this->postJson('/blood-connect/donor/resend-otp')->assertTooManyRequests();
        $this->travel(1)->minutes();
        $this->post('/blood-connect/donor/resend-otp')->assertRedirect()->assertSessionHas('dev_otp');
        $code = session('dev_otp');
        $this->postJson('/blood-connect/donor/verify-phone', ['code' => $code])->assertTooManyRequests();
        $this->travel(15)->minutes();
        $this->postJson('/blood-connect/donor/verify-phone', ['code' => $code])->assertUnprocessable();
    }

    public function test_hospital_login_does_not_authenticate_donor_and_donor_login_does_not_authenticate_hospital(): void
    {
        $hospitalUser = User::factory()->patient()->create();
        $donor = BloodDonor::factory()->independent()->create();
        $this->actingAs($hospitalUser, 'web')->get('/blood-connect/donor/dashboard')->assertRedirect('/blood-connect/donor/login');
        Auth::guard('web')->logout();
        $this->post('/blood-connect/donor/login', ['phone' => $donor->phone, 'password' => 'donor-password'])->assertRedirect('/blood-connect/donor/dashboard');
        $this->assertAuthenticatedAs($donor, 'donor');
        $this->assertGuest('web');
        $this->get('/blood-connect/donor/dashboard')->assertOk();
        foreach (['patient', 'doctor', 'admin', 'receptionist', 'lab-assistant'] as $portal) {
            $this->get('/'.$portal.'/dashboard')->assertRedirect(route('login'));
        }
        $this->post('/blood-connect/donor/logout')->assertRedirect('/blood-connect/donor/login');
        $this->assertGuest('donor');
        $this->get('/blood-connect/donor/dashboard')->assertRedirect('/blood-connect/donor/login');
    }

    public function test_donor_logout_preserves_explicitly_authenticated_hospital_identity(): void
    {
        $user = User::factory()->patient()->create();
        $donor = BloodDonor::factory()->independent()->create();
        $this->actingAs($user, 'web');
        $this->post('/blood-connect/donor/login', ['phone' => $donor->phone, 'password' => 'donor-password'])->assertRedirect();
        $this->post('/blood-connect/donor/logout')->assertRedirect();
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('donor');
        $this->get('/patient/dashboard')->assertOk();
    }

    public function test_hospital_password_is_not_a_donor_password_and_login_is_rate_limited(): void
    {
        $user = User::factory()->patient()->create();
        $donor = BloodDonor::factory()->independent()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/blood-connect/donor/login', ['phone' => $donor->phone, 'password' => 'password'])->assertUnprocessable();
        }
        $this->postJson('/blood-connect/donor/login', ['phone' => $donor->phone, 'password' => 'donor-password'])->assertTooManyRequests();
        $this->assertGuest('donor');
        $this->assertGuest('web');
    }

    public function test_donor_authentication_survives_hospital_logout_using_its_own_cookie(): void
    {
        $hospitalUser = User::factory()->patient()->create();
        $donor = BloodDonor::factory()->independent()->create();
        $this->actingAs($hospitalUser, 'web');
        $login = $this->post('/blood-connect/donor/login', ['phone' => $donor->phone, 'password' => 'donor-password'])->assertRedirect('/blood-connect/donor/dashboard');
        $cookie = $login->getCookie(StartDonorSession::COOKIE);
        $this->assertSame('/blood-connect', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertFalse(session()->has(Auth::guard('donor')->getName()));
        $this->post('/logout')->assertRedirect('/');
        Auth::forgetGuards();
        $this->withCookie(StartDonorSession::COOKIE, $cookie->getValue())->get('/blood-connect/donor/dashboard')->assertOk();
        $this->assertAuthenticatedAs($donor, 'donor');
        $this->assertGuest('web');
        $logout = $this->post('/blood-connect/donor/logout')->assertRedirect('/blood-connect/donor/login');
        Auth::forgetGuards();
        $this->withCookie(StartDonorSession::COOKIE, $logout->getCookie(StartDonorSession::COOKIE)->getValue())->get('/blood-connect/donor/dashboard')->assertRedirect('/blood-connect/donor/login');
    }

    #[TestWith(['phone', '01212345678'])]
    #[TestWith(['district', 'Not a district'])]
    #[TestWith(['password_confirmation', 'different-password'])]
    #[TestWith(['password', 'short'])]
    #[TestWith(['last_donation_date', '2999-01-01'])]
    public function test_invalid_registration_is_rejected_without_creating_accounts(string $field, string $value): void
    {
        $this->post('/blood-connect/donor/register', $this->registration([$field => $value]))->assertSessionHasErrors($field === 'password_confirmation' ? 'password' : $field);
        $this->assertDatabaseCount('blood_donors', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_duplicate_phone_is_rejected_and_directory_consent_can_be_withdrawn(): void
    {
        $donor = BloodDonor::factory()->independent()->phoneVerified()->create(['phone' => '01712345678']);
        $this->post('/blood-connect/donor/register', $this->registration())->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('blood_donors', 1);
        $this->actingAs($donor, 'donor')->post('/blood-connect/donor/preferences', ['availability_status' => 'available', 'public_profile' => '0', 'phone_verified_at' => null, 'user_id' => 999])->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/blood-connect/donors')->assertDontSee($donor->full_name);
        $this->assertNotNull($donor->fresh()->phone_verified_at);
        $this->assertNull($donor->fresh()->user_id);
    }

    public function test_password_byte_and_null_limits_reject_invalid_values_without_hashing_errors(): void
    {
        foreach ([str_repeat('a', 73), str_repeat('আ', 25), "valid\0password"] as $password) {
            $this->post('/blood-connect/donor/register', $this->registration(['password' => $password, 'password_confirmation' => $password]))->assertSessionHasErrors('password');
        }
        $this->assertDatabaseCount('blood_donors', 0);
    }

    public function test_otp_issued_for_a_previous_phone_cannot_verify_a_changed_phone(): void
    {
        $donor = BloodDonor::factory()->independent()->create();
        DonorOtp::factory()->create(['donor_id' => $donor->id]);
        $donor->phone = '01611111111';
        $donor->save();
        $this->actingAs($donor, 'donor')->post('/blood-connect/donor/verify-phone', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertNull($donor->fresh()->phone_verified_at);
    }
}
