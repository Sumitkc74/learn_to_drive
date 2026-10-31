<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('school_lesson_payments',function(Blueprint $t){
            $t->uuid('id')->primary(); $t->foreignId('lesson_booking_id')->unique()->constrained()->restrictOnDelete();
            $t->unsignedInteger('amount_paisa'); $t->string('gateway',20); $t->boolean('live');
            $t->string('merchant')->nullable(); $t->string('provider_id')->nullable()->unique(); $t->text('checkout_url')->nullable();
            $t->string('transaction_id')->nullable(); $t->unique(['gateway','transaction_id']);
            $t->string('status',30)->default('Pending'); $t->timestamp('paid_at')->nullable();
            $t->string('refund_reference')->nullable()->unique(); $t->timestamp('refunded_at')->nullable(); $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('school_lesson_payments'); }
};
