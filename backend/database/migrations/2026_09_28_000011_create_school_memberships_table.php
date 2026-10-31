<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driving_school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestamps();
            $table->unique(['driving_school_id', 'user_id']);
            $table->index(['driving_school_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_memberships');
    }
};
