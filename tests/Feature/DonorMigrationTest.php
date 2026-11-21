<?php

namespace Tests\Feature;

use App\Models\BloodDonationRecord;
use App\Models\BloodDonor;
use App\Models\BloodRequestDonor;
use App\Models\DonorContactRequest;
use App\Models\DonorPasswordReset;
use App\Services\DonorIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DonorMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_historical_migration_preserves_profiles_and_blood_rows_and_generates_one_time_reset_report(): void
    {
        $donor = BloodDonor::factory()->phoneVerified()->create();
        $unverified = BloodDonor::factory()->create();
        $independent = BloodDonor::factory()->independent()->create();
        $interest = BloodRequestDonor::factory()->create(['blood_donor_id' => $donor->id]);
        $donation = BloodDonationRecord::factory()->create(['blood_donor_id' => $donor->id]);
        $before = $donor->fresh()->getRawOriginal();
        $report = storage_path('app/donor-migration-'.Str::uuid().'.csv');
        try {
            $this->artisan('blood-connect:migrate-donors', ['--report' => $report])->expectsOutputToContain('Migrated 2 historical donor(s)')->assertSuccessful();
            $rows = array_map(fn (string $line): array => str_getcsv($line, ',', '"', ''), file($report, FILE_IGNORE_NEW_LINES));
            $this->assertCount(3, $rows);
            $this->assertSame($donor->phone, $rows[1][1]);
            $this->assertNotNull($donor->fresh()->password);
            $this->assertFalse(Hash::check('password', $donor->fresh()->password));
            foreach ($before as $key => $value) {
                if (! in_array($key, ['password', 'updated_at'], true)) {
                    $this->assertSame($value, $donor->fresh()->getRawOriginal($key), $key);
                }
            }
            $this->assertNull($unverified->fresh()->phone_verified_at);
            $this->assertModelExists($interest);
            $this->assertModelExists($donation);
            $this->assertSame($donor->id, $donation->fresh()->blood_donor_id);
            $this->assertTrue(Hash::check('donor-password', $independent->fresh()->password));
            $this->assertDatabaseCount('donor_password_resets', 2);
            $resetUrl = $rows[1][2];
            $token = basename(parse_url($resetUrl, PHP_URL_PATH));
            $this->assertSame(hash('sha256', $token), DonorPasswordReset::where('donor_id', $donor->id)->sole()->token_hash);
            $this->get($resetUrl)->assertOk()->assertDontSee($donor->phone);
            $this->post('/blood-connect/donor/reset-password/'.$donor->id, ['token' => $token, 'password' => 'reset-donor-password', 'password_confirmation' => 'reset-donor-password'])->assertRedirect('/blood-connect/donor/login')->assertSessionHasNoErrors();
            $this->assertTrue(Hash::check('reset-donor-password', $donor->fresh()->password));
            $this->post('/blood-connect/donor/reset-password/'.$donor->id, ['token' => $token, 'password' => 'another-password', 'password_confirmation' => 'another-password'])->assertSessionHasErrors('token');
            $this->artisan('blood-connect:migrate-donors', ['--report' => $report])->assertFailed();
        } finally {
            if (is_file($report)) {
                unlink($report);
            }
        }
    }

    public function test_expired_and_wrong_donor_reset_tokens_are_rejected(): void
    {
        $this->freezeTime();
        $donor = BloodDonor::factory()->independent()->create();
        $other = BloodDonor::factory()->independent()->create();
        $token = app(DonorIdentityService::class)->createResetToken($donor);
        $payload = ['token' => $token, 'password' => 'reset-password', 'password_confirmation' => 'reset-password'];
        $this->post('/blood-connect/donor/reset-password/'.$other->id, $payload)->assertSessionHasErrors('token');
        $this->travel(24)->hours();
        $this->post('/blood-connect/donor/reset-password/'.$donor->id, $payload)->assertSessionHasErrors('token');
        $this->assertTrue(Hash::check('donor-password', $donor->fresh()->password));
    }

    public function test_contact_requests_do_not_block_existing_hospital_account_deletion(): void
    {
        $donor = BloodDonor::factory()->create();
        $contact = DonorContactRequest::factory()->create(['blood_donor_id' => $donor->id]);
        $this->actingAs($donor->user, 'web')->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');
        $this->assertModelMissing($donor);
        $this->assertModelMissing($contact);
    }
}
