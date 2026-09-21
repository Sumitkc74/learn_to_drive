<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('government_notice_imports', function (Blueprint $table) {
            $table->id();
            $table->string('source_name');
            $table->text('source_url');
            $table->string('source_domain');
            $table->string('title', 500);
            $table->string('content_hash', 64)->unique();
            $table->string('status')->default('Pending')->index();
            $table->timestamp('fetched_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('notices', function (Blueprint $table) {
            $table->string('source_name')->nullable()->after('expires_at');
            $table->text('source_url')->nullable()->after('source_name');
            $table->foreignId('government_notice_import_id')->nullable()->after('source_url')->constrained('government_notice_imports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('government_notice_import_id');
            $table->dropColumn(['source_name', 'source_url']);
        });
        Schema::dropIfExists('government_notice_imports');
    }
};
