<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_seed_admin')->default(false)->after('role');
        });

        DB::table('users')
            ->where('email', 'admin@admin.com')
            ->where('role', 'Admin')
            ->update(['is_seed_admin' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_seed_admin');
        });
    }
};
