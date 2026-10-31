<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('school_student_exams', function (Blueprint $t) {
            $t->id();
            $t->foreignId('driving_school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('category',30); $t->string('exam_type',20);
            $t->date('exam_date'); $t->string('centre',160)->nullable();
            $t->string('result',20)->default('Scheduled'); $t->date('result_date')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('version')->default(1); $t->timestamps();
            $t->unique(['driving_school_id','user_id','category','exam_type','exam_date'],'school_exam_attempt_unique');
            $t->index(['driving_school_id','exam_date','result']);
        });
        Schema::create('school_student_licences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('driving_school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('category',30); $t->date('issued_on')->nullable(); $t->date('expires_on')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('version')->default(1); $t->timestamps();
            $t->unique(['driving_school_id','user_id','category'],'school_student_licence_unique');
        });
    }
    public function down(): void {
        Schema::dropIfExists('school_student_licences');
        Schema::dropIfExists('school_student_exams');
    }
};
