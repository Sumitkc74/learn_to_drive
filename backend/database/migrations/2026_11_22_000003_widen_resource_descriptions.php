<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The admin forms validate these fields longer than the VARCHAR(255)
     * columns can hold, so on live MySQL every over-long save threw
     * "Data too long" and the site showed a 500 (SQLite does not enforce
     * VARCHAR length, which is why the suite stayed green):
     *
     * - descriptions validated at max:1000 (tutorials, exam information,
     *   exam papers, traffic signs, notices) — and up to max:5000 via the
     *   notice bulk import — widened to TEXT;
     * - questions.question validated at max:500 — widened to VARCHAR(500);
     * - tutorials.videoLink and notices.link validated at max:2048 —
     *   widened to VARCHAR(2048).
     */
    public function up(): void
    {
        Schema::table('tutorials', function (Blueprint $table) {
            $table->text('description')->change();
            $table->string('videoLink', 2048)->change();
        });

        Schema::table('exam_information', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('exam_papers', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('traffic_signs', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('notices', function (Blueprint $table) {
            $table->text('description')->change();
            $table->text('nepaliDescription')->change();
            $table->string('link', 2048)->nullable()->change();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('question', 500)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tutorials', function (Blueprint $table) {
            $table->string('description')->change();
            $table->string('videoLink')->change();
        });

        Schema::table('exam_information', function (Blueprint $table) {
            $table->string('description')->change();
        });

        Schema::table('exam_papers', function (Blueprint $table) {
            $table->string('description')->change();
        });

        Schema::table('traffic_signs', function (Blueprint $table) {
            $table->string('description')->change();
        });

        Schema::table('notices', function (Blueprint $table) {
            $table->string('description')->change();
            $table->string('nepaliDescription')->change();
            $table->string('link')->nullable()->change();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('question')->change();
        });
    }
};
