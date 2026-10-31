<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('premium_payments', function (Blueprint $table) {
            $table->string('refund_status')->nullable()->after('status');
            $table->text('refund_reason')->nullable()->after('refund_status');
            $table->timestamp('refunded_at')->nullable()->after('refund_reason');
        });
    }

    public function down(): void
    {
        Schema::table('premium_payments', function (Blueprint $table) {
            $table->dropColumn(['refund_status', 'refund_reason', 'refunded_at']);
        });
    }
};
