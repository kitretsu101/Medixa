<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Tracker\MedicationNotificationService;
use Illuminate\Console\Command;

class SendMedicationRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch pending dose reminders and safety warnings to active patients';

    /**
     * Execute the console command.
     */
    public function handle(MedicationNotificationService $notificationService): int
    {
        $this->info('Checking for due medication doses...');
        $sentReminders = $notificationService->sendDueDoseReminders();
        $this->info("Dispatched {$sentReminders} dose reminders.");

        // Check for 2-consecutive-day missed warnings
        $patients = User::patients()->get();
        $warningCount = 0;
        foreach ($patients as $patient) {
            if ($notificationService->checkAndWarnConsecutiveMissedDays($patient)) {
                $warningCount++;
            }
        }

        if ($warningCount > 0) {
            $this->warn("Dispatched {$warningCount} clinical safety warnings for 2-consecutive missed days.");
        }

        return self::SUCCESS;
    }
}

