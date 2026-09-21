<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_papers', fn (Blueprint $table) => $table->string('language')->default('English')->index());
        DB::transaction(function () {
            foreach (DB::table('exam_papers')->orderBy('id')->get() as $row) {
                $media = DB::table('media')->where('model_type', (new \App\Models\ExamPaper)->getMorphClass())
                    ->where('model_id', $row->id)->orderBy('order_column')->orderBy('id')->get();
                if ($media->isEmpty()) {
                    DB::table('exam_papers')->where('id', $row->id)->update(['language' => $row->nepaliFile && !$row->englishFile ? 'Nepali' : 'English']);
                }
                foreach ($media as $index => $file) {
                    $properties = json_decode($file->custom_properties, true) ?: [];
                    $language = $properties['language'] ?? ($file->order_column == 2 || (!$row->englishFile && $row->nepaliFile) ? 'Nepali' : 'English');
                    if (!in_array($language, ['English', 'Nepali'], true)) throw new RuntimeException('Unknown question bank language.');
                    $id = $row->id;
                    if ($index > 0) {
                        $copy = (array) $row; unset($copy['id']);
                        $copy['language'] = $language;
                        $id = DB::table('exam_papers')->insertGetId($copy);
                    } else {
                        DB::table('exam_papers')->where('id', $id)->update(['language' => $language]);
                    }
                    $properties['language'] = $language;
                    DB::table('media')->where('id', $file->id)->update(['model_id' => $id, 'order_column' => 1, 'custom_properties' => json_encode($properties)]);
                }
            }
        });
        Schema::table('exam_papers', fn (Blueprint $table) => $table->dropColumn(['englishFile', 'nepaliFile']));
    }
    public function down(): void
    {
        Schema::table('exam_papers', function (Blueprint $table) {
            $table->string('englishFile')->default('');
            $table->string('nepaliFile')->default('');
        });
        foreach (DB::table('exam_papers')->get() as $row) {
            $file = DB::table('media')->where('model_type', (new \App\Models\ExamPaper)->getMorphClass())->where('model_id', $row->id)->first();
            if ($file) {
                DB::table('exam_papers')->where('id', $row->id)->update([$row->language === 'Nepali' ? 'nepaliFile' : 'englishFile' => $file->file_name]);
            }
        }
        Schema::table('exam_papers', function (Blueprint $table) { $table->dropIndex(['language']); $table->dropColumn('language'); });
    }
};
