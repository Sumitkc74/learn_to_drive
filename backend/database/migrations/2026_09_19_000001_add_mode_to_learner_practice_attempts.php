<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('learner_practice_attempts',fn(Blueprint $table)=>$table->string('mode',20)->default('practice')); }
    public function down(): void { Schema::table('learner_practice_attempts',fn(Blueprint $table)=>$table->dropColumn('mode')); }
};
