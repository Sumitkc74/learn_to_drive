<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->timestamp('answer_verified_at')->nullable();
            $table->unsignedBigInteger('answer_verified_by')->nullable();
            $table->string('answer_review_hash', 64)->nullable();
        });
        Schema::create('official_source_checks', function (Blueprint $table) {
            $table->string('source_key', 80)->primary();
            $table->string('status', 20);
            $table->timestamp('checked_at');
            $table->unsignedInteger('found')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_source_checks');
        Schema::table('questions', fn (Blueprint $table) => $table->dropColumn(['answer_verified_at', 'answer_verified_by', 'answer_review_hash']));
    }
};
