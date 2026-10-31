<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lesson_vehicles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('driving_school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $t->string('name', 100);
            $t->string('registration', 40)->unique();
            $t->string('transmission', 20);
            $t->timestamps();
        });
        Schema::create('driving_lessons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lesson_vehicle_id')->constrained()->cascadeOnDelete();
            $t->dateTime('starts_at')->index();
            $t->dateTime('ends_at');
            $t->unsignedInteger('price_npr');
            $t->string('meeting_point', 255);
            $t->boolean('cancelled')->default(false);
            $t->timestamps();
        });
        Schema::create('lesson_bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('driving_lesson_id')->constrained()->cascadeOnDelete();
            $t->foreignId('learner_id')->constrained('users')->cascadeOnDelete();
            // Releasing this unique claim allows rebooking without deleting history.
            $t->foreignId('active_lesson_id')->nullable()->unique()->constrained('driving_lessons')->cascadeOnDelete();
            $t->string('status', 20)->default('Booked');
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('lesson_bookings');
        Schema::dropIfExists('driving_lessons');
        Schema::dropIfExists('lesson_vehicles');
    }
};
