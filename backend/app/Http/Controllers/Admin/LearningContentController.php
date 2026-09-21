<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\LearningContentImport;
use App\Models\TrafficSign;
use App\Services\LearningContentCollector;
use App\Services\LearningContentDownload;
use App\Support\AdminTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LearningContentController extends Controller
{
    public function storeVision(Request $request, LearningContentImport $resource)
    {
        $data = $request->validate([
            'testNumber' => ['required', 'integer', 'min:1', 'unique:vision_tests,testNumber'],
            'confirmed' => ['accepted'],
            'file_hash' => ['required', 'string', 'size:64'],
        ]);
        DB::transaction(function () use ($resource, $data) {
            $source = LearningContentImport::lockForUpdate()->findOrFail($resource->id);
            $disk = Storage::disk('learning-content');
            abort_unless($source->kind === 'vision-test-image' && $source->status === 'Approved'
                && $source->file_path && $disk->exists($source->file_path), 409, 'Approve the saved reference first.');
            $bytes = $disk->get($source->file_path);
            abort_unless(hash_equals((string) $source->file_hash, hash('sha256', $bytes))
                && hash_equals((string) $source->file_hash, $data['file_hash']), 409, 'The source image has changed.');
            $key = hash('sha256', 'vision:'.$source->file_hash);
            if (Media::where('custom_properties->extraction_key', $key)->exists()) {
                throw ValidationException::withMessages(['image' => 'This image has already been added to Vision Tests.']);
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            abort_unless(isset($extensions[$mime]) && @getimagesizefromstring($bytes)
                && strlen($bytes) <= AppSetting::imageLimitKb() * 1024, 422, 'Unsupported image or image exceeds the upload limit.');
            $name = 'vision-'.$source->id.'.'.$extensions[$mime];
            $test = \App\Models\VisionTest::create(['testNumber' => $data['testNumber'], 'image' => $name]);
            $test->addMediaFromString($bytes)->usingFileName($name)->withCustomProperties([
                'extraction_key' => $key, 'source_resource_id' => $source->id,
                'source_url' => $source->source_url, 'asset_url' => $source->asset_url,
                'source_hash' => $source->file_hash, 'reviewed_by' => auth()->id(),
            ])->toMediaCollection();
        });

        return redirect()->route('learningContent.show', $resource)->with('success', 'Image added to Vision Tests.');
    }

    public function extract(LearningContentImport $resource)
    {
        abort_unless($resource->kind === 'traffic-sign-sheet' && $resource->status !== 'Rejected' && $resource->file_path && Storage::disk('learning-content')->exists($resource->file_path), 404);

        return view('admin.learning-content.extract', compact('resource'));
    }

    public function storeSign(Request $request, LearningContentImport $resource)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nepaliSignName' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'x' => ['required', 'integer', 'min:0'],
            'y' => ['required', 'integer', 'min:0'],
            'width' => ['required', 'integer', 'min:16'],
            'height' => ['required', 'integer', 'min:16'],
            'confirmed' => ['accepted'],
        ]);
        abort_unless(function_exists('imagecreatefromstring'), 503, 'Enable the PHP GD extension to extract signs.');
        DB::transaction(function () use ($resource, $data) {
            $source = LearningContentImport::lockForUpdate()->findOrFail($resource->id);
            $disk = Storage::disk('learning-content');
            abort_unless($source->kind === 'traffic-sign-sheet' && $source->status !== 'Rejected' && $source->file_path && $disk->exists($source->file_path), 409);
            $bytes = $disk->get($source->file_path);
            abort_unless(hash_equals((string) $source->file_hash, hash('sha256', $bytes)), 409, 'The source image has changed.');
            $size = @getimagesizefromstring($bytes);
            abort_unless($size && $size[0] * $size[1] <= 40000000 && $size[0] >= $data['x'] + $data['width'] && $size[1] >= $data['y'] + $data['height'], 422, 'Select a rectangle inside the source image.');
            $crop = array_map('intval', collect($data)->only(['x', 'y', 'width', 'height'])->all());
            $key = hash('sha256', $source->id.':'.$source->file_hash.':'.json_encode($crop));
            if (Media::where('custom_properties->extraction_key', $key)->exists()) {
                throw ValidationException::withMessages(['image' => 'This selection has already been saved as a traffic sign.']);
            }
            $image = @imagecreatefromstring($bytes);
            abort_unless($image, 422, 'The source image could not be decoded.');
            $cropped = imagecrop($image, $crop);
            imagedestroy($image);
            abort_unless($cropped, 422, 'The selection could not be extracted.');
            ob_start();
            imagepng($cropped);
            $png = ob_get_clean();
            imagedestroy($cropped);
            abort_if(strlen($png) > AppSetting::imageLimitKb() * 1024, 422, 'The selected image exceeds the image upload limit.');
            $sign = TrafficSign::create(collect($data)->only(['name', 'nepaliSignName', 'description'])->all() + ['image' => 'sign.png']);
            $sign->addMediaFromString($png)->usingFileName('sign-'.$sign->id.'.png')->withCustomProperties([
                'extraction_key' => $key, 'source_resource_id' => $source->id,
                'source_url' => $source->source_url, 'asset_url' => $source->asset_url,
                'source_hash' => $source->file_hash, 'crop' => $crop, 'reviewed_by' => auth()->id(),
            ])->toMediaCollection();
        });

        return redirect()->route('learningContent.extract', $resource)->with('success', 'Individual traffic sign saved. You can select another sign from this sheet.');
    }

    public function index(Request $request)
    {
        $sources = config('official-content.sources');
        $categories = ['question-bank-document' => 'Questions', 'traffic-sign-sheet' => 'Traffic signs', 'vision-test-image' => 'Vision tests'];
        $category = $request->query('kind');
        if (!is_string($category) || !array_key_exists($category, $categories)) {
            $category = '';
        }
        if ($category !== '') {
            $sources = array_filter($sources, fn ($source) => $source['kind'] === $category);
        }
        $imports = AdminTable::paginate(LearningContentImport::with(['creator', 'reviewer']), $request,
            ['title', 'source_name', 'edition'], ['id', 'title', 'status', 'created_at', 'fetched_at'], [
                'status' => ['allowed' => ['Pending', 'Approved', 'Rejected']],
                'kind' => ['allowed' => array_keys($categories)],
            ]);

        return view('admin.learning-content.index', compact('imports', 'sources', 'categories', 'category'));
    }

    public function fetch(Request $request, LearningContentCollector $collector)
    {
        $data = $request->validate(['source' => ['required', Rule::in(array_keys(config('official-content.sources')))]]);
        try {
            $result = $collector->fetch($data['source']);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['source' => 'The official source could not be checked. Please try again later.']);
        }

        return redirect()->route('learningContent', ['kind' => config('official-content.sources.'.$data['source'].'.kind')])->with('success', "Source checked: {$result['added']} new, {$result['known']} already known, {$result['excluded']} excluded links.");
    }

    public function show(LearningContentImport $resource)
    {
        $resource->load(['creator', 'reviewer']);
        $hasFile = $resource->file_path && Storage::disk('learning-content')->exists($resource->file_path);
        return view('admin.learning-content.show', compact('resource', 'hasFile'));
    }

    public function download(LearningContentImport $resource, LearningContentDownload $downloader)
    {
        abort_unless($resource->status === 'Pending', 409, 'This resource has already been reviewed.');
        if ($resource->file_path && Storage::disk('learning-content')->exists($resource->file_path)) {
            return back()->with('success', 'A private copy is already available.');
        }
        try {
            $file = $downloader->fetch($resource);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['download' => 'Download failed. Check the source availability, file type, and upload size limit.']);
        }
        try {
            DB::transaction(function () use ($resource, $file) {
                $locked = LearningContentImport::lockForUpdate()->findOrFail($resource->id);
                abort_unless($locked->status === 'Pending', 409, 'This resource has already been reviewed.');
            abort_if($locked->assigned_to && $locked->assigned_to !== auth()->id(),409,'This resource is assigned to another admin.');
                // Do not replace a copy another reviewer may already be inspecting.
                if ($locked->file_path && Storage::disk('learning-content')->exists($locked->file_path)) {
                    Storage::disk('learning-content')->delete($file['file_path']);

                    return;
                }
                $locked->forceFill($file)->save();
            });
        } catch (\Throwable $exception) {
            Storage::disk('learning-content')->delete($file['file_path']);
            throw $exception;
        }

        return redirect()->route('learningContent.show', $resource)->with('success', 'Private copy saved. Inspect it before approving this reference.');
    }

    public function file(Request $request, LearningContentImport $resource)
    {
        abort_unless($resource->file_path && Storage::disk('learning-content')->exists($resource->file_path), 404);

        if ($request->boolean('inline') && $resource->mime_type === 'application/pdf') {
            return Storage::disk('learning-content')->response($resource->file_path, 'review.pdf', ['Content-Type'=>'application/pdf','X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store']);
        }
        if ($request->boolean('preview')) {
            abort_unless(in_array($resource->kind, ['traffic-sign-sheet', 'vision-test-image'], true) && in_array($resource->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

            return Storage::disk('learning-content')->response($resource->file_path, null, [
                'Content-Type' => $resource->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ]);
        }

        return Storage::disk('learning-content')->download($resource->file_path, 'official-resource-'.$resource->id.'.'.pathinfo($resource->file_path, PATHINFO_EXTENSION), [
            'Content-Type' => $resource->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function review(Request $request, LearningContentImport $resource)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'title' => ['required_if:decision,Approved', 'nullable', 'string', 'max:500'],
            'language' => ['required_if:decision,Approved', 'nullable', Rule::in(['Nepali', 'English', 'Bilingual', 'Not applicable'])],
            'licence_category' => ['required_if:decision,Approved', 'nullable', Rule::in(['A/K', 'B', 'All', 'Other', 'Unknown'])],
            'edition' => ['required_if:decision,Approved', 'nullable', 'string', 'max:100'],
            'review_notes' => ['required', 'string', 'max:3000'],
            'confirmed' => ['required_if:decision,Approved', 'accepted_if:decision,Approved'],
        ]);
        DB::transaction(function () use ($resource, $data) {
            $locked = LearningContentImport::lockForUpdate()->findOrFail($resource->id);
            abort_unless($locked->status === 'Pending', 409, 'This resource has already been reviewed.');
            abort_if($locked->assigned_to && $locked->assigned_to !== auth()->id(),409,'This resource is assigned to another admin.');
            if ($data['decision'] === 'Approved') {
                app(\App\Services\ContentAiScreening::class)->requireScreened($locked);
                $disk = Storage::disk('learning-content');
                abort_unless($locked->file_path && $disk->exists($locked->file_path), 409, 'Save and inspect a private copy first.');
                abort_unless(hash_equals((string) $locked->file_hash, hash_file('sha256', $disk->path($locked->file_path))), 409, 'The saved file has changed. Approval was blocked.');
                $locked->forceFill(collect($data)->only(['title', 'language', 'licence_category', 'edition'])->all());
            }
            $locked->forceFill(['status' => $data['decision'], 'review_notes' => $data['review_notes'], 'reviewed_by' => auth()->id(), 'reviewed_at' => now()])->save();
        });

        return redirect()->route('learningContent.show', $resource)->with('success', $data['decision'] === 'Approved'
            ? 'Reference approved. It is available for content preparation and has not been published to learners.'
            : 'Resource rejected. Future source checks will not re-add it.');
    }
}
