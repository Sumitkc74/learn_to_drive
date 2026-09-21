<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', fn (Blueprint $table) => $table->index(['status', 'answer_verified_at'], 'question_readiness_verification'));
        Schema::table('pdf_question_imports', fn (Blueprint $table) => $table->index(['question_id', 'needs_diagram'], 'question_readiness_diagrams'));
        Schema::table('pdf_extraction_runs', fn (Blueprint $table) => $table->index(['status', 'updated_at'], 'extraction_progress_health'));
    }

    public function down(): void
    {
        Schema::table('questions', fn (Blueprint $table) => $table->dropIndex('question_readiness_verification'));
        Schema::table('pdf_question_imports', fn (Blueprint $table) => $table->dropIndex('question_readiness_diagrams'));
        Schema::table('pdf_extraction_runs', fn (Blueprint $table) => $table->dropIndex('extraction_progress_health'));
    }
};
