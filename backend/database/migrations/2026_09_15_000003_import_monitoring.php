<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('content_source_runs',function(Blueprint $t){
            $t->id(); $t->string('source'); $t->string('status'); $t->unsignedInteger('added')->default(0);
            $t->unsignedInteger('known')->default(0); $t->text('error')->nullable(); $t->timestamp('started_at'); $t->timestamp('finished_at')->nullable();
        });
        Schema::create('content_ai_usage',function(Blueprint $t){
            $t->date('day')->primary(); $t->unsignedInteger('requests')->default(0); $t->unsignedBigInteger('tokens')->default(0);
        });
    }
    public function down(): void { Schema::dropIfExists('content_source_runs'); Schema::dropIfExists('content_ai_usage'); }
};
