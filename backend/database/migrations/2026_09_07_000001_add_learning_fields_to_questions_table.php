<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('category')->default('General')->after('question');
            $table->string('difficulty')->default('Medium')->after('category');
            $table->text('explanation')->nullable()->after('correctOption');
            $table->string('status')->default('Published')->after('explanation');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['category', 'difficulty', 'explanation', 'status']);
        });
    }
};
