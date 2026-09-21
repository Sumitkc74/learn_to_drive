<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        foreach (['learning_content_imports', 'government_notice_imports'] as $name) Schema::table($name, function (Blueprint $t) {
            $t->string('ai_status')->nullable()->index();
            $t->text('ai_report')->nullable();
            $t->string('ai_hash',64)->nullable();
            $t->timestamp('ai_checked_at')->nullable();
        });
    }
    public function down(): void {
        foreach (['learning_content_imports', 'government_notice_imports'] as $name) Schema::table($name, function(Blueprint $t) {
            $t->dropIndex(['ai_status']);
            $t->dropColumn(['ai_status','ai_report','ai_hash','ai_checked_at']);
        });
    }
};
