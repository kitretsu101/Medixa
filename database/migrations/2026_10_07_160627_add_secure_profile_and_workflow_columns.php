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
        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->text('emergency_contact')->nullable()->change();
            $table->text('phone')->nullable();
            $table->text('nid')->nullable();
        });

        Schema::table('assistance_requests', function (Blueprint $table) {
            $table->text('response')->nullable()->after('message');
            $table->timestamp('responded_at')->nullable()->after('status');
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('consent_token_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });

        Schema::table('assistance_requests', function (Blueprint $table) {
            $table->dropColumn(['response', 'responded_at']);
        });

        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->dropColumn(['phone', 'nid']);
            $table->string('emergency_contact', 20)->nullable()->change();
        });
    }
};
