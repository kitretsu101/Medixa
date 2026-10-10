<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'lab_assistant', 'receptionist') NOT NULL DEFAULT 'patient'");

        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending', 'confirmed', 'checked_in', 'waiting', 'in_progress', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'pending'");

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('priority_type', 32)->default('normal')->after('status');
            $table->string('visit_type', 32)->default('scheduled')->after('priority_type');
            $table->timestamp('checked_in_at')->nullable()->after('visit_type');
            $table->timestamp('consultation_started_at')->nullable()->after('checked_in_at');
            $table->timestamp('consultation_ended_at')->nullable()->after('consultation_started_at');
            $table->unsignedTinyInteger('last_queue_notice')->nullable()->after('consultation_ended_at');
            $table->index(['doctor_id', 'appointment_date', 'status'], 'appointments_doctor_date_status_index');
        });

        Schema::table('live_queues', function (Blueprint $table) {
            $table->string('session_status', 32)->default('not_started')->after('doctor_present_status');
            $table->unsignedSmallInteger('delay_minutes')->default(0)->after('session_status');
        });

        Schema::create('queue_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 64);
            $table->string('explanation', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['doctor_id', 'created_at']);
        });

        Schema::create('appointment_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('waiting_rating');
            $table->unsignedTinyInteger('doctor_rating');
            $table->unsignedTinyInteger('system_rating');
            $table->string('comment', 1000)->nullable();
            $table->timestamps();
        });

        Schema::table('lab_reports', function (Blueprint $table) {
            $table->string('clinical_outcome', 64)->nullable()->after('review_status');
            $table->timestamp('reviewed_at')->nullable()->after('clinical_outcome');
            $table->string('review_note', 500)->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_reports', function (Blueprint $table) {
            $table->dropColumn(['clinical_outcome', 'reviewed_at', 'review_note']);
        });

        Schema::dropIfExists('appointment_feedback');
        Schema::dropIfExists('queue_events');

        Schema::table('live_queues', function (Blueprint $table) {
            $table->dropColumn(['session_status', 'delay_minutes']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_doctor_date_status_index');
            $table->dropColumn([
                'priority_type',
                'visit_type',
                'checked_in_at',
                'consultation_started_at',
                'consultation_ended_at',
                'last_queue_notice',
            ]);
        });

        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'lab_assistant') NOT NULL DEFAULT 'patient'");
    }
};
