<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('notice_reads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('notice_id')->constrained()->cascadeOnDelete();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'notice_id']);
            $t->index(['user_id', 'read_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('notice_reads'); }
};
