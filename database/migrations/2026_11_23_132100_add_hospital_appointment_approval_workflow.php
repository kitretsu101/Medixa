<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitals', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('hospital_id')->nullable()->constrained()->restrictOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('hospital_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'waiting', 'in_progress', 'completed', 'cancelled', 'no_show', 'approved', 'rejected'])->default('pending')->change();
            $table->index(['hospital_id', 'status', 'appointment_date']);
        });
    }

    public function down(): void
    {
        if (DB::table('hospitals')->exists()
            || DB::table('users')->whereNotNull('hospital_id')->exists()
            || DB::table('appointments')->whereNotNull('hospital_id')->orWhereNotNull('reason')->exists()
            || DB::table('appointments')->whereIn('status', ['approved', 'rejected'])->exists()) {
            throw new RuntimeException('Hospital approval data exists. Use a forward migration rather than deleting assignments or approval history.');
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['hospital_id']);
            $table->dropIndex(['hospital_id', 'status', 'appointment_date']);
            $table->dropColumn(['hospital_id', 'reason']);
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'waiting', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('pending')->change();
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('hospital_id');
        });
        Schema::dropIfExists('hospitals');
    }
};
