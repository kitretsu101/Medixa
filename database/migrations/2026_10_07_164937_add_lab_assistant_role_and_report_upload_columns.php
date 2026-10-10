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
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'lab_assistant') NOT NULL DEFAULT 'patient'");

        Schema::table('lab_reports', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('id');
            $table->string('upload_status', 32)->default('uploaded')->after('review_status');
            $table->foreignId('uploaded_by')->nullable()->after('uploaded_at')->constrained('users')->nullOnDelete();
            $table->string('file_path')->nullable()->change();
            $table->timestamp('uploaded_at')->nullable()->change();
        });

        $reports = DB::table('lab_reports')->whereNull('reference')->orderBy('id')->get();

        foreach ($reports as $report) {
            $year = date('Y', strtotime((string) $report->created_at));

            DB::table('lab_reports')->where('id', $report->id)->update([
                'reference' => sprintf('LAB-%s-%05d', $year, $report->id),
            ]);
        }

        Schema::table('lab_reports', function (Blueprint $table) {
            $table->unique('reference');
            $table->index('upload_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_reports', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropIndex(['upload_status']);
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn(['reference', 'upload_status']);
            $table->string('file_path')->nullable(false)->change();
            $table->timestamp('uploaded_at')->nullable(false)->useCurrent()->change();
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient'");
    }
};
