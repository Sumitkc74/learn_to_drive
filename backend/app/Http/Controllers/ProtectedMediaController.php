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
        return $disk->response($media->getPathRelativeToRoot(),$media->file_name,[
            'Content-Type'=>$media->mime_type,'X-Content-Type-Options'=>'nosniff',
            'Cache-Control'=>'private, no-store','Content-Security-Policy'=>"default-src 'none'; sandbox",
        ]);
    }
}
