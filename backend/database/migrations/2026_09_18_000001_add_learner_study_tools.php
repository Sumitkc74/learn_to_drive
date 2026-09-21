<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('language', 2)->default('en')->index();
        });
        DB::table('questions')->select('id','question')->orderBy('id')->chunkById(200, function ($questions) {
            foreach ($questions as $question) DB::table('questions')->where('id',$question->id)->update([
                'language'=>preg_match('/\p{Devanagari}/u',$question->question ?? '') ? 'ne' : 'en',
            ]);
        });
        Schema::create('learner_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('content_type', 32);
            $table->unsignedBigInteger('content_id');
            $table->timestamps();
            $table->unique(['user_id','content_type','content_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('learner_bookmarks');
        Schema::table('questions', fn(Blueprint $table)=>$table->dropIndex(['language']));
        Schema::table('questions', fn(Blueprint $table)=>$table->dropColumn('language'));
    }
};
