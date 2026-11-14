<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('driving_schools',function(Blueprint $t){
            $t->unsignedInteger('commission_percent')->nullable();
            $t->unsignedInteger('enrollment_fee_paisa')->nullable();
            $t->unsignedInteger('proposed_commission_percent')->nullable();
            $t->unsignedInteger('proposed_enrollment_fee_paisa')->nullable();
        });
    }
    public function down(): void {
        Schema::table('driving_schools',function(Blueprint $t){
            $t->dropColumn(['commission_percent','enrollment_fee_paisa','proposed_commission_percent','proposed_enrollment_fee_paisa']);
        });
    }
};
