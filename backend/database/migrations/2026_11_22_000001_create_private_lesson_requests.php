<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('private_lesson_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('learner_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('driving_school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('lesson_vehicle_id')->constrained()->cascadeOnDelete();
            $t->foreignId('driving_lesson_id')->nullable()->constrained()->nullOnDelete();
            $t->dateTime('starts_at');
            $t->integer('duration_minutes');
            $t->string('meeting_point', 255);
            $t->text('note')->nullable();
            $t->string('status', 20)->default('Pending');
            $t->string('decline_reason', 500)->nullable();
            $t->timestamps();
            $t->index(['instructor_id', 'status']);
            $t->index(['learner_id', 'status']);
            $t->index(['driving_school_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('private_lesson_requests'); }
};
