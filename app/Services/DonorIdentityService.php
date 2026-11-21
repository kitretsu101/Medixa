<?php

namespace App\Services;

use App\Models\BloodDonor;
use App\Models\DonorOtp;
use App\Models\DonorPasswordReset;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DonorIdentityService
{
    /** @param array<string, mixed> $data */
    public function register(array $data): BloodDonor
    {
        try {
            return DB::transaction(function () use ($data): BloodDonor {
                $donor = new BloodDonor;
                $donor->fill(array_intersect_key($data, array_flip(['full_name', 'blood_group', 'phone', 'district', 'area', 'last_donation_date', 'availability_status', 'public_profile', 'contact_consent'])));
                $donor->password = $data['password'];
                $donor->user_id = null;
                $donor->phone_verified_at = null;
                $donor->save();

                return $donor;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (! BloodDonor::where('phone', $data['phone'])->exists()) {
                throw $exception;
            }
            throw ValidationException::withMessages(['phone' => 'This phone number already has a donor account.']);
        }
    }

    public function sendOtp(BloodDonor $donor): ?string
    {
        $code = DB::transaction(function () use ($donor): ?string {
            $locked = BloodDonor::whereKey($donor->id)->lockForUpdate()->firstOrFail();
            if ($locked->phone_verified_at !== null) {
                return null;
            }
            $previous = DonorOtp::where('donor_id', $locked->id)->first();
            if ($previous && $previous->updated_at->gt(now()->subMinute())) {
                throw ValidationException::withMessages(['code' => 'Please wait one minute before requesting another code.']);
            }
            $code = (string) random_int(100000, 999999);
            DonorOtp::updateOrCreate(['donor_id' => $locked->id], ['code_hash' => Hash::make($locked->phone.'|'.$code), 'expires_at' => now()->addMinutes(10), 'attempts' => 0]);

            return $code;
        });

        if ($code !== null) {
            if (app()->environment(['local', 'testing'])) {
                Log::info('DEVELOPMENT SMS STUB: donor phone verification code', ['donor_id' => $donor->id, 'code' => $code]);

                return $code;
            }
            Log::warning('Donor SMS gateway is not configured; verification code was not sent.', ['donor_id' => $donor->id]);
        }

        return null;
    }

    public function verifyOtp(BloodDonor $donor, string $code): void
    {
        $verified = DB::transaction(function () use ($donor, $code): bool {
            $locked = BloodDonor::whereKey($donor->id)->lockForUpdate()->firstOrFail();
            if ($locked->phone_verified_at !== null) {
                return true;
            }
            $otp = DonorOtp::where('donor_id', $locked->id)->lockForUpdate()->first();
            if (! $otp || $otp->expires_at->lte(now()) || $otp->attempts >= 5) {
                return false;
            }
            $otp->increment('attempts');
            if (! Hash::check($locked->phone.'|'.$code, $otp->code_hash)) {
                return false;
            }
            $locked->phone_verified_at = now();
            $locked->save();
            $otp->delete();

            return true;
        });

        if (! $verified) {
            throw ValidationException::withMessages(['code' => 'The verification code is invalid, expired, or has reached its attempt limit.']);
        }
    }

    public function createResetToken(BloodDonor $donor): string
    {
        $token = Str::random(64);
        DonorPasswordReset::updateOrCreate(['donor_id' => $donor->id], ['token_hash' => hash('sha256', $token), 'expires_at' => now()->addHours(24)]);

        return $token;
    }

    public function resetPassword(BloodDonor $donor, string $token, string $password): void
    {
        DB::transaction(function () use ($donor, $token, $password): void {
            $locked = BloodDonor::whereKey($donor->id)->lockForUpdate()->firstOrFail();
            $reset = DonorPasswordReset::where('donor_id', $locked->id)->lockForUpdate()->first();
            if (! $reset || $reset->expires_at->lte(now()) || ! hash_equals($reset->token_hash, hash('sha256', $token))) {
                throw ValidationException::withMessages(['token' => 'This reset link is invalid or expired.']);
            }
            $locked->password = $password;
            $locked->remember_token = Str::random(60);
            $locked->save();
            $reset->delete();
        });
    }
}
