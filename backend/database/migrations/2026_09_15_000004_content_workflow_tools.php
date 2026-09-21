<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('content_versions',function(Blueprint $t){
            $t->id(); $t->string('content_type'); $t->unsignedBigInteger('content_id'); $t->unsignedBigInteger('actor_id')->nullable();
            $t->json('snapshot'); $t->timestamp('created_at'); $t->index(['content_type','content_id']);
        });
        Schema::create('scraping_sources',function(Blueprint $t){$t->string('key')->primary();$t->boolean('enabled')->default(true);$t->string('daily_time',5)->default('02:10');$t->timestamps();});
        foreach(['learning_content_imports','government_notice_imports'] as $name) Schema::table($name,function(Blueprint $t){
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('claimed_at')->nullable();
        });
    }
    public function down(): void {
        foreach(['learning_content_imports','government_notice_imports'] as $name) Schema::table($name,function(Blueprint $t){$t->dropConstrainedForeignId('assigned_to');$t->dropColumn('claimed_at');});
        Schema::dropIfExists('scraping_sources'); Schema::dropIfExists('content_versions');
    }
};
