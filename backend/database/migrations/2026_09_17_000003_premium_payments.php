<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void {
        Schema::table('users',fn(Blueprint $t)=>$t->timestamp('premium_until')->nullable());
        Schema::create('premium_payments',function(Blueprint $t){
            $t->uuid('id')->primary();$t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('gateway');$t->boolean('live');$t->string('merchant')->nullable();
            $t->unsignedInteger('amount_paisa');$t->unsignedInteger('access_days');
            $t->string('status')->default('Pending');$t->string('provider_id')->nullable()->unique();
            $t->string('transaction_id')->nullable();$t->timestamp('paid_at')->nullable();$t->timestamp('access_until')->nullable();
            $t->timestamps();$t->unique(['gateway','transaction_id']);
        });
    }
    public function down():void {Schema::dropIfExists('premium_payments');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('premium_until'));}
};
