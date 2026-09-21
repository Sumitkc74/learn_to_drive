<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_question_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_content_import_id')->constrained()->restrictOnDelete();
            $table->string('fingerprint', 64)->unique();
            $table->string('source_hash', 64);
            $table->unsignedInteger('page');
            $table->unsignedInteger('number');
            $table->json('payload');
            $table->text('raw_text');
            $table->json('warnings');
            $table->string('status', 20)->default('Pending')->index();
            $table->unsignedBigInteger('question_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_question_imports');
    }
};
