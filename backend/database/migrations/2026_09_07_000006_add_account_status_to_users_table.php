<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Do NOT use after('is_seed_admin') here: that column is only
            // added by 2026_09_07_010000, which runs AFTER this migration,
            // so MySQL fails with "Unknown column 'is_seed_admin'".
            // SQLite ignores after() entirely, which is why the test
            // suite never caught this. Column order is cosmetic, so
            // anchor to 'role', which the original users table has.
            $table->boolean('is_active')->default(true)->after('role')->index();
            $table->timestamp('suspended_at')->nullable()->after('is_active');
            $table->text('suspension_reason')->nullable()->after('suspended_at');
            $table->timestamp('last_login_at')->nullable()->after('suspension_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_active', 'suspended_at', 'suspension_reason', 'last_login_at']);
        });
    }
};
