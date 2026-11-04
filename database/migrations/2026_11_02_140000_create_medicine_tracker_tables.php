<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medication_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('duration_days')->default(7);
            $table->string('status', 30)->default('active'); // active, completed, superseded, cancelled
            $table->string('timezone', 64)->default('UTC');
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index('prescription_id');
        });

        Schema::create('medication_doses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_schedule_id')->constrained('medication_schedules')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->string('medicine_name', 255);
            $table->string('dosage', 100);
            $table->string('slot', 30); // morning, afternoon, evening, night
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->text('instructions')->nullable();
            $table->string('status', 30)->default('pending'); // pending, taken, missed, skipped
            $table->timestamp('taken_at')->nullable();
            $table->timestamps();

            $table->unique(['medication_schedule_id', 'medicine_name', 'scheduled_date', 'scheduled_time'], 'med_doses_sched_med_date_time_unique');
            $table->index(['patient_id', 'scheduled_date']);
            $table->index(['medication_schedule_id', 'status']);
        });

        Schema::create('adherence_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_dose_id')->constrained('medication_doses')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30); // taken, missed, skipped
            $table->text('reason')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['patient_id', 'recorded_at']);
            $table->index('medication_dose_id');
        });

        Schema::create('recovery_journeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->foreignId('medication_schedule_id')->constrained('medication_schedules')->cascadeOnDelete();
            $table->unsignedSmallInteger('current_day')->default(1);
            $table->string('current_state', 30)->default('S0_SEED'); // S0_SEED, S1_SPROUT, S2_GROWING, S3_BLOOM, W_WILTED
            $table->decimal('adherence_rate', 5, 2)->default(100.00);
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('consecutive_missed_days')->default(0);
            $table->string('status', 30)->default('active'); // active, completed, paused
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index('medication_schedule_id');
        });

        Schema::create('recovery_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recovery_journey_id')->constrained('recovery_journeys')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->string('reward_type', 50); // first_dose, streak_milestone, sprout_stage, growing_stage, full_bloom
            $table->string('badge_name', 100);
            $table->unsignedInteger('points')->default(10);
            $table->text('description');
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->index(['patient_id', 'recovery_journey_id']);
        });

        Schema::create('tracker_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->string('status', 30)->default('pending'); // pending, activated, dismissed
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->unique(['patient_id', 'prescription_id']);
            $table->index(['patient_id', 'status']);
        });

        Schema::create('tracker_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('medication_dose_id')->nullable()->constrained('medication_doses')->nullOnDelete();
            $table->string('type', 50); // reminder, missed_alert, milestone, consecutive_warning
            $table->string('channel', 30)->default('inapp'); // webpush, database, inapp
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 30)->default('sent'); // sent, failed, suppressed
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'type', 'scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracker_notification_logs');
        Schema::dropIfExists('tracker_invitations');
        Schema::dropIfExists('recovery_rewards');
        Schema::dropIfExists('recovery_journeys');
        Schema::dropIfExists('adherence_records');
        Schema::dropIfExists('medication_doses');
        Schema::dropIfExists('medication_schedules');
    }
};

