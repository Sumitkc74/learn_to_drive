<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('pdf_translations', function (Blueprint $t) {
            $t->id(); $t->foreignId('learning_content_import_id')->constrained();
            $t->string('source_language', 10); $t->string('target_language', 10);
            $t->char('source_hash', 64); $t->string('status', 20)->default('Queued');
            $t->unsignedInteger('next_page')->default(1); $t->unsignedInteger('total_pages')->nullable();
            $t->unsignedBigInteger('created_by'); $t->unsignedBigInteger('output_resource_id')->nullable();
            $t->timestamps();
        });
        Schema::create('pdf_translation_pages', function (Blueprint $t) {
            $t->id(); $t->foreignId('pdf_translation_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('page'); $t->longText('source_text'); $t->longText('translated_text');
            $t->unsignedBigInteger('reviewed_by')->nullable(); $t->timestamp('reviewed_at')->nullable();
            $t->timestamps(); $t->unique(['pdf_translation_id','page']);
        });
    }
    public function down(): void { Schema::dropIfExists('pdf_translation_pages'); Schema::dropIfExists('pdf_translations'); }
};
