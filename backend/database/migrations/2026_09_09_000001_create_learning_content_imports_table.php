<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_content_imports', function (Blueprint $table) {
            $table->id();
            $table->string('source_key', 80);
            $table->string('source_name', 255);
            $table->text('source_url');
            $table->text('asset_url');
            $table->char('url_hash', 64)->unique();
            $table->string('kind', 40)->index();
            $table->string('title', 500);
            $table->string('status', 20)->default('Pending')->index();
            $table->string('language', 30)->nullable();
            $table->string('licence_category', 40)->nullable();
            $table->string('edition', 100)->nullable();
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('fetched_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('mime_type', 80)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->char('file_hash', 64)->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_content_imports');
    }
};
