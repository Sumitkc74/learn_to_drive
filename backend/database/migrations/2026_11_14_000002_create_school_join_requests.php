<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('school_join_requests',function(Blueprint $t){
            $t->id(); $t->foreignId('driving_school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('status',20)->default('Pending'); $t->timestamps();
            $t->index(['driving_school_id','status']); $t->index(['user_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('school_join_requests'); }
};
