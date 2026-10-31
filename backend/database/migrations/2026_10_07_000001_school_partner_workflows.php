<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('school_memberships', fn (Blueprint $t) => $t->boolean('share_progress')->default(false));
        Schema::create('school_invitations', function (Blueprint $t) {
            $t->id(); $t->foreignId('driving_school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->string('role',20);
            $t->string('status',20)->default('Pending'); $t->timestamp('expires_at'); $t->timestamps();
            $t->unique(['driving_school_id','user_id']); $t->index(['user_id','status']);
        });
        Schema::create('school_courses', function (Blueprint $t) {
            $t->id(); $t->foreignId('driving_school_id')->constrained()->cascadeOnDelete();
            $t->string('title',160); $t->text('description'); $t->boolean('archived')->default(false); $t->timestamps();
        });
        Schema::create('school_batches', function (Blueprint $t) {
            $t->id(); $t->foreignId('school_course_id')->constrained()->cascadeOnDelete();
            $t->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $t->string('name',100); $t->date('starts_on'); $t->date('ends_on'); $t->unsignedInteger('capacity');
            $t->boolean('closed')->default(false); $t->timestamps();
        });
        Schema::create('school_batch_members', function (Blueprint $t) {
            $t->id(); $t->foreignId('school_batch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->timestamps(); $t->unique(['school_batch_id','user_id']);
        });
        Schema::create('school_class_sessions', function (Blueprint $t) {
            $t->id(); $t->foreignId('school_batch_id')->constrained()->cascadeOnDelete();
            $t->dateTime('starts_at'); $t->dateTime('ends_at'); $t->string('location');
            $t->boolean('cancelled')->default(false); $t->timestamps(); $t->index(['school_batch_id','starts_at']);
        });
        Schema::create('school_attendance', function (Blueprint $t) {
            $t->id(); $t->foreignId('school_class_session_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->string('status',20);
            $t->foreignId('marked_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
            $t->unique(['school_class_session_id','user_id']);
        });
        Schema::create('school_notifications', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('message'); $t->timestamp('read_at')->nullable(); $t->timestamps(); $t->index(['user_id','read_at']);
        });
    }
    public function down(): void {
        foreach (['school_notifications','school_attendance','school_class_sessions','school_batch_members','school_batches','school_courses','school_invitations'] as $table) Schema::dropIfExists($table);
        Schema::table('school_memberships', fn (Blueprint $t) => $t->dropColumn('share_progress'));
    }
};
