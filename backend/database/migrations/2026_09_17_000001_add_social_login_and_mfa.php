<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('users',function(Blueprint $table){
        $table->string('google_id')->nullable()->unique();
        $table->text('mfa_secret')->nullable();
        $table->text('mfa_recovery_codes')->nullable();
        $table->unsignedBigInteger('mfa_last_step')->nullable();
    }); }
    public function down(): void { Schema::table('users',function(Blueprint $table){
        $table->dropUnique(['google_id']);
        $table->dropColumn(['google_id','mfa_secret','mfa_recovery_codes','mfa_last_step']);
    }); }
};
