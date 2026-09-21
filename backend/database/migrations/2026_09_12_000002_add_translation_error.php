<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('pdf_translations', fn (Blueprint $table) => $table->string('error', 500)->nullable()); }
    public function down(): void { Schema::table('pdf_translations', fn (Blueprint $table) => $table->dropColumn('error')); }
};
