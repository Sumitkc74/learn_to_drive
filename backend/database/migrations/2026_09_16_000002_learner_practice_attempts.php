<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {Schema::create('learner_practice_attempts',function(Blueprint $t){
        $t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->json('questions');$t->json('answers')->nullable();
        $t->unsignedInteger('score')->nullable();$t->timestamp('completed_at')->nullable();$t->timestamp('expires_at');$t->timestamps();
    });}
    public function down(): void {Schema::dropIfExists('learner_practice_attempts');}
};
