<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        foreach (['learning_content_imports','government_notice_imports'] as $name) Schema::table($name, function (Blueprint $t) {
            $t->unsignedTinyInteger('ai_retry_count')->default(0);
            $t->timestamp('ai_activity_at')->nullable();
        });
    }
    public function down(): void {
        foreach (['learning_content_imports','government_notice_imports'] as $name) Schema::table($name, fn(Blueprint $t) => $t->dropColumn(['ai_retry_count','ai_activity_at']));
    }
};
