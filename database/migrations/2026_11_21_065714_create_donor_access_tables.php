<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donor_otps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('donor_id')->unique()->constrained('blood_donors')->cascadeOnDelete();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });
        Schema::create('donor_password_resets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('donor_id')->unique()->constrained('blood_donors')->cascadeOnDelete();
            $table->string('token_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::create('donor_contact_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blood_donor_id')->constrained()->cascadeOnDelete();
            $table->string('requester_name')->nullable();
            $table->string('requester_phone', 20);
            $table->string('blood_group_needed', 3);
            $table->unsignedSmallInteger('units_needed');
            $table->string('hospital_name');
            $table->string('location');
            $table->date('required_date');
            $table->enum('emergency_level', ['normal', 'urgent', 'critical']);
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'contacted', 'closed'])->default('pending');
            $table->timestamps();
            $table->index(['blood_donor_id', 'requester_phone', 'created_at'], 'donor_contact_cooldown');
            $table->index(['blood_donor_id', 'status', 'created_at'], 'donor_contact_inbox');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donor_contact_requests');
        Schema::dropIfExists('donor_password_resets');
        Schema::dropIfExists('donor_otps');
    }
};
