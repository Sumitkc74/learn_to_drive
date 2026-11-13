<?php
namespace App\Http\Controllers;
use App\Models\Question;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class ProtectedMediaController extends Controller {
    public function show(Media $media) {
        abort_unless($media->disk === 'protected-media' && $media->model_type === (new Question)->getMorphClass(),404);
        $owner = Question::withTrashed()->find($media->model_id);
        abort_unless($owner,404);
        if (!request()->routeIs('media.review')) abort_unless($owner->status === 'Published' && !$owner->trashed(),404);
        $disk=Storage::disk('protected-media');
        abort_unless($disk->exists($media->getPathRelativeToRoot()),404);
        // Published images are public content addressed by media id, so
        // browsers may cache them for an hour (archival revokes access
        // within that window). The admin review route stays no-store.
        $isReview = request()->routeIs('media.review');
        return $disk->response($media->getPathRelativeToRoot(),$media->file_name,[
            'Content-Type'=>$media->mime_type,'X-Content-Type-Options'=>'nosniff',
            'Cache-Control'=>$isReview ? 'private, no-store' : 'public, max-age=3600, must-revalidate',
            'Content-Security-Policy'=>"default-src 'none'; sandbox",
        ]);
    }
}
