<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('learning_content_imports',function(Blueprint $t){
            $t->unsignedBigInteger('parent_resource_id')->nullable()->index();
            $t->timestamp('source_checked_at')->nullable();
            $t->string('source_check_status')->nullable();
        });
    }
    public function down(): void {
        Schema::table('learning_content_imports',function(Blueprint $t){$t->dropIndex(['parent_resource_id']);$t->dropColumn(['parent_resource_id','source_checked_at','source_check_status']);});
    }
};
