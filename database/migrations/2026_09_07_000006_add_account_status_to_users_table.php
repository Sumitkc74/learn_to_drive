<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_seed_admin')->index();
            $table->timestamp('suspended_at')->nullable()->after('is_active');
            $table->text('suspension_reason')->nullable()->after('suspended_at');
            $table->timestamp('last_login_at')->nullable()->after('suspension_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['is_active', 'suspended_at', 'suspension_reason', 'last_login_at']));
    }
};
