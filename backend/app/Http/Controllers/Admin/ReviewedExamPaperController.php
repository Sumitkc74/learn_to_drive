<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ExamPaper;
use App\Models\LearningContentImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ReviewedExamPaperController extends Controller
{
    public function store(Request $request, LearningContentImport $resource)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'file_hash' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'confirmed' => ['accepted'],
        ]);
        $ids = [$resource->id];
        $selections = [[$resource->id, $data['file_hash']]];
        DB::transaction(function () use ($data, $selections, $ids) {
            $sources = LearningContentImport::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            abort_unless($sources->count() === count($selections), 409, 'A selected reference is unavailable.');
            $disk = Storage::disk('learning-content');
            $files = [];
            foreach ($selections as $index => [$id, $hash]) {
                $source = $sources->get((int) $id);
                abort_unless($source->kind === 'question-bank-document' && $source->status === 'Approved'
                    && in_array($source->language, ['English', 'Nepali'], true)
                    && $source->file_path && $disk->exists($source->file_path), 409, 'Select an approved PDF with a language category and saved copy.');
                \Illuminate\Support\Facades\Validator::make($data, [
                    'name' => [new \App\Rules\DocumentLanguage($source->language)],
                    'description' => [new \App\Rules\DocumentLanguage($source->language)],
                ])->validate();
                $path = $disk->path($source->file_path);
                abort_unless(hash_equals((string) $source->file_hash, $hash)
                    && hash_equals($hash, hash_file('sha256', $path)), 409, 'A source PDF has changed. Reload and review it again.');
                abort_unless(filesize($path) <= AppSetting::documentLimitKb() * 1024
                    && (new \finfo(FILEINFO_MIME_TYPE))->file($path) === 'application/pdf', 422, 'Invalid PDF or document exceeds the upload limit.');
                if (Media::where('model_type', (new ExamPaper)->getMorphClass())
                    ->where('custom_properties->source_hash', $hash)->exists()) {
                    throw ValidationException::withMessages(['file_hash' => 'This PDF is already in Question Banks.']);
                }
                $files[$index] = [$source, $path];
            }
            foreach ($files as [$source, $path]) {
                $paper = ExamPaper::create(collect($data)->only(['name', 'description'])->all() + ['language' => $source->language]);
                $media = $paper->addMedia($path)->preservingOriginal()
                    ->usingFileName('question-bank-'.strtolower($source->language).'.pdf')
                    ->withCustomProperties([
                        'source_resource_id' => $source->id, 'source_url' => $source->source_url,
                        'asset_url' => $source->asset_url, 'source_hash' => $source->file_hash,
                        'document_type' => 'question-bank', 'language' => $source->language,
                        'reviewed_by' => $source->reviewed_by, 'added_by' => auth()->id(),
                    ])->toMediaCollection();
                $media->order_column = 1;
                $media->save();
            }
        });

        return redirect()->route('allExamPaper')->with('success', 'Reviewed question bank added to Question Banks.');
    }
}
