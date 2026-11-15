<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('driving_schools',function(Blueprint $t){
            $t->dateTime('rates_declined_at')->nullable();
            $t->string('rates_declined_reason',500)->nullable();
            $t->unsignedBigInteger('rates_declined_by')->nullable();
        });
    }
    public function down(): void {
        Schema::table('driving_schools',function(Blueprint $t){
            $t->dropColumn(['rates_declined_at','rates_declined_reason','rates_declined_by']);
        });
    }
};
