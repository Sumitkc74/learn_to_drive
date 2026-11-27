<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The audit observer stores the observed record's name/question/title
     * as subject_label. Questions are VARCHAR(500) since the November 22
     * widen, so a long question overflowed this VARCHAR(255) column:
     * SQLite never enforces the length so the suite stayed green, but
     * MySQL and Postgres both throw (22001) and the whole request fails.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->text('subject_label')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('subject_label', 255)->nullable()->change();
        });
    }
};
