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
        Schema::create('medical_knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('topic', 64);
            $table->string('language', 2);
            $table->text('content');
            $table->string('source_url')->nullable();
            $table->string('source_name');
            $table->date('review_date')->nullable();
            $table->string('reviewer_name')->nullable();
            $table->string('approval_status', 32)->default('pending');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['topic', 'language']);
            $table->index(['language', 'approval_status', 'is_active', 'topic'], 'knowledge_retrieval_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_knowledge_articles');
    }
};
