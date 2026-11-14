<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('school_lesson_payments',function(Blueprint $t){
            $t->unsignedInteger('commission_paisa')->nullable();
            $t->unsignedInteger('school_payout_paisa')->nullable();
        });
    }
    public function down(): void {
        Schema::table('school_lesson_payments',function(Blueprint $t){
            $t->dropColumn(['commission_paisa','school_payout_paisa']);
        });
    }
};
