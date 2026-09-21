<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
return new class extends Migration {
    public function up(): void
    {
        DB::table('exam_papers')->where('language', 'Nepali')->whereNotNull('nepaliName')
            ->whereRaw("TRIM(nepaliName) <> ''")->update(['name' => DB::raw('nepaliName')]);
        Schema::table('exam_papers', fn (Blueprint $table) => $table->dropColumn('nepaliName'));
    }
    public function down(): void
    {
        Schema::table('exam_papers', fn (Blueprint $table) => $table->string('nepaliName')->default(''));
        DB::table('exam_papers')->where('language', 'Nepali')->update(['nepaliName' => DB::raw('name')]);
    }
};
