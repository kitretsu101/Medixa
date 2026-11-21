<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $previousPhone = null;
        foreach (DB::table('blood_donors')->orderBy('phone')->cursor(['phone']) as $donor) {
            if ($previousPhone === $donor->phone) {
                throw new RuntimeException('Duplicate donor phone numbers must be resolved before enabling donor login. No profile fields were changed.');
            }
            $previousPhone = $donor->phone;
        }
        Schema::table('blood_donors', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->unique('phone');
            $table->index(['phone_verified_at', 'public_profile', 'availability_status'], 'donor_directory_visibility');
        });
    }

    /** Keep user_id nullable: independent donors cannot be assigned historical hospital identities. */
    public function down(): void
    {
        Schema::table('blood_donors', function (Blueprint $table): void {
            $table->dropIndex('donor_directory_visibility');
            $table->dropUnique(['phone']);
            $table->dropColumn(['password', 'remember_token']);
        });
    }
};
