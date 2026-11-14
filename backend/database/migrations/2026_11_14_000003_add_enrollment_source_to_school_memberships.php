<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('school_memberships',function(Blueprint $t){
            $t->string('enrollment_source',20)->nullable();
            $t->unsignedInteger('enrollment_fee_paisa')->default(0);
        });
    }
    public function down(): void {
        Schema::table('school_memberships',function(Blueprint $t){
            $t->dropColumn(['enrollment_source','enrollment_fee_paisa']);
        });
    }
};
