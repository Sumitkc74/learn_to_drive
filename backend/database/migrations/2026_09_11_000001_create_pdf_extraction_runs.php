<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_extraction_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_content_import_id')->constrained()->restrictOnDelete();
            $table->string('source_hash', 64);
            $table->string('mode', 10);
            $table->string('status', 20)->default('Queued')->index();
            $table->unsignedInteger('from_page');
            $table->unsignedInteger('to_page');
            $table->unsignedInteger('next_page');
            $table->unsignedInteger('generation')->default(1);
            $table->unsignedInteger('added')->default(0);
            $table->unsignedInteger('known')->default(0);
            $table->boolean('cancel_requested')->default(false);
            $table->text('error')->nullable();
            $table->json('empty_pages')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamps();
            $table->index(['learning_content_import_id', 'status']);
        });
        Schema::create('pdf_extraction_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_extraction_jobs');
        Schema::dropIfExists('pdf_extraction_runs');
    }
};
