<?php

use App\Support\QuestionReviewIndex;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['questions', 'pdf_question_imports'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->text('normalized_question')->nullable();
                $table->string('question_hash', 64)->nullable()->index();
            });
        }
        Schema::table('pdf_question_imports', function (Blueprint $table) {
            foreach (['needs_answer', 'needs_diagram', 'incomplete_options', 'is_ocr'] as $flag) {
                $table->boolean($flag)->default(false);
            }
            $table->index(['learning_content_import_id', 'status', 'page', 'number', 'id'], 'pdf_review_order');
            $table->index(['learning_content_import_id', 'page', 'number', 'id'], 'pdf_bank_order');
            $table->index(['status', 'needs_answer'], 'pdf_pending_answers');
            $table->index(['status', 'is_ocr'], 'pdf_pending_ocr');
        });
        DB::table('questions')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('questions')->where('id', $row->id)->update(QuestionReviewIndex::question($row->question));
            }
        });
        DB::table('pdf_question_imports')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('pdf_question_imports')->where('id', $row->id)->update(QuestionReviewIndex::candidate(json_decode($row->payload, true) ?: [], json_decode($row->warnings, true) ?: []));
            }
        });
    }

    public function down(): void
    {
        Schema::table('pdf_question_imports', function (Blueprint $table) {
            foreach (['pdf_review_order', 'pdf_bank_order', 'pdf_pending_answers', 'pdf_pending_ocr'] as $index) {
                $table->dropIndex($index);
            } $table->dropColumn(['needs_answer', 'needs_diagram', 'incomplete_options', 'is_ocr']);
        });
        foreach (['questions', 'pdf_question_imports'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['question_hash']);
                $table->dropColumn(['normalized_question', 'question_hash']);
            });
        }
    }
};
