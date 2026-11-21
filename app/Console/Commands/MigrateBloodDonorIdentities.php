<?php

namespace App\Console\Commands;

use App\Models\BloodDonor;
use App\Services\DonorIdentityService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('blood-connect:migrate-donors {--report= : New CSV file path; contains sensitive reset links}')]
#[Description('Initialize independent credentials for historical donor profiles without changing profile fields')]
class MigrateBloodDonorIdentities extends Command
{
    public function handle(DonorIdentityService $identity): int
    {
        $path = $this->option('report');
        if (! is_string($path) || $path === '') {
            $this->error('Supply --report with a new, private CSV file path.');

            return self::FAILURE;
        }
        $file = @fopen($path, 'x');
        if ($file === false) {
            $this->error('Could not create the report. The file must not already exist and its directory must be writable.');

            return self::FAILURE;
        }
        $count = 0;
        try {
            $this->writeRow($file, ['donor_id', 'phone', 'reset_link', 'expires_at']);
            BloodDonor::whereNotNull('user_id')->whereNull('password')->chunkById(100, function ($donors) use ($identity, $file, &$count): void {
                foreach ($donors as $donor) {
                    DB::transaction(function () use ($identity, $file, $donor, &$count): void {
                        $locked = BloodDonor::whereKey($donor->id)->lockForUpdate()->firstOrFail();
                        if ($locked->password !== null) {
                            return;
                        }
                        $locked->password = Str::random(64);
                        $locked->save();
                        $token = $identity->createResetToken($locked);
                        $this->writeRow($file, [(string) $locked->id, $locked->phone, route('blood-connect.donor.password.edit', ['bloodDonor' => $locked->id, 'token' => $token]), now()->addHours(24)->toIso8601String()]);
                        $count++;
                    });
                }
            });
        } finally {
            fclose($file);
        }
        $this->info("Migrated {$count} historical donor(s). Phone verification values and linked blood records were preserved.");
        $this->line('Private CSV report: '.$path);

        return self::SUCCESS;
    }

    /** @param resource $file
     * @param  list<string>  $values
     */
    private function writeRow($file, array $values): void
    {
        if (fputcsv($file, $values, ',', '"', '') === false || ! fflush($file)) {
            throw new \RuntimeException('The private donor migration report could not be written.');
        }
    }
}
