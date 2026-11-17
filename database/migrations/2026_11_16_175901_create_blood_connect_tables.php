<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_code', 40)->unique();
            $table->string('submission_hash', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('patient_name');
            $table->string('blood_group', 3);
            $table->unsignedSmallInteger('units_required');
            $table->unsignedSmallInteger('units_fulfilled')->default(0);
            $table->string('hospital_name');
            $table->string('district', 100);
            $table->string('area');
            $table->date('required_date');
            $table->string('contact_name');
            $table->string('contact_number', 20);
            $table->enum('emergency_level', ['normal', 'urgent', 'critical']);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'active', 'rejected', 'fulfilled', 'expired'])->default('pending');
            $table->boolean('contact_consent')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->dateTime('expires_at');
            $table->timestamps();
            $table->index(['status', 'expires_at']);
            $table->index(['blood_group', 'district']);
        });
        Schema::create('blood_donors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('blood_group', 3);
            $table->string('phone', 20);
            $table->string('district', 100);
            $table->string('area');
            $table->date('last_donation_date')->nullable();
            $table->enum('availability_status', ['available', 'temporarily_unavailable', 'unavailable'])->default('unavailable');
            $table->timestamp('phone_verified_at')->nullable();
            $table->boolean('public_profile')->default(false);
            $table->boolean('contact_consent')->default(false);
            $table->timestamps();
            $table->index(['district', 'availability_status']);
        });
        Schema::create('blood_request_donors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blood_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blood_donor_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('interested');
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['blood_request_id', 'blood_donor_id']);
        });
        Schema::create('blood_donation_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blood_request_id')->constrained()->restrictOnDelete();
            $table->foreignId('blood_donor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('donation_date');
            $table->unsignedSmallInteger('units_donated');
            $table->string('hospital_name');
            $table->string('status', 30)->default('verified');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['blood_request_id', 'blood_donor_id', 'donation_date'], 'blood_donation_once_per_day');
        });
        Schema::create('blood_request_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blood_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 30);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_request_events');
        Schema::dropIfExists('blood_donation_records');
        Schema::dropIfExists('blood_request_donors');
        Schema::dropIfExists('blood_donors');
        Schema::dropIfExists('blood_requests');
    }
};
