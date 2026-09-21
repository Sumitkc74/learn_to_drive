<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->string('status')->default('Published')->after('link')->index();
            $table->timestamp('publish_at')->nullable()->after('status')->index();
            $table->timestamp('expires_at')->nullable()->after('publish_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            foreach (['status', 'publish_at', 'expires_at'] as $column) $table->dropIndex([$column]);
            $table->dropColumn(['status', 'publish_at', 'expires_at']);
        });
    }
};
