<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void {
        Schema::create('lesson_waitlist',function(Blueprint $t){
            $t->id();$t->foreignId('driving_lesson_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('status',20)->default('Waiting');$t->timestamps();
            $t->unique(['driving_lesson_id','user_id']);$t->index(['status','driving_lesson_id']);
        });
        Schema::create('lesson_booking_changes',function(Blueprint $t){
            $t->id();$t->foreignId('lesson_booking_id')->constrained()->cascadeOnDelete();
            $t->foreignId('from_lesson_id')->constrained('driving_lessons')->restrictOnDelete();
            $t->foreignId('to_lesson_id')->constrained('driving_lessons')->restrictOnDelete();$t->timestamps();
        });
        Schema::create('school_lesson_feedback',function(Blueprint $t){
            $t->id();$t->foreignId('school_class_session_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $t->string('skill',100);$t->string('assessment',30);$t->text('notes');$t->timestamps();
            $t->unique(['school_class_session_id','user_id','skill'],'school_feedback_skill_unique');
        });
        Schema::create('school_reminder_deliveries',function(Blueprint $t){
            $t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('reminder_key',160);$t->timestamps();
            $t->unique(['user_id','reminder_key']);
        });
    }
    public function down():void {
        foreach(['school_reminder_deliveries','school_lesson_feedback','lesson_booking_changes','lesson_waitlist'] as $table) Schema::dropIfExists($table);
    }
};
