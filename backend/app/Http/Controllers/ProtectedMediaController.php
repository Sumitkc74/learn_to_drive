<?php
namespace App\Http\Controllers;
use App\Models\Question;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class ProtectedMediaController extends Controller {
    public function show(Media $media) {
        abort_unless($media->model_type === (new Question)->getMorphClass(),404);
        $owner = Question::withTrashed()->find($media->model_id);
        abort_unless($owner,404);
        if (!request()->routeIs('media.review')) abort_unless($owner->status === 'Published' && !$owner->trashed(),404);
        $isReview = request()->routeIs('media.review');
        // Question images now live on the shared media disk (R2 in
        // production), like the banks and plates: they already have a
        // public URL, so the public route redirects to it instead of
        // proxying the bytes. The admin review route still streams
        // through the app, so its no-store header actually applies
        // (a redirect would let the browser cache the R2 response).
        if ($media->disk !== 'protected-media' && !$isReview) {
            return redirect()->away($media->getUrl())->header('Cache-Control','public, max-age=3600, must-revalidate');
        }
        $disk=Storage::disk($media->disk);
        abort_unless($disk->exists($media->getPathRelativeToRoot()),404);
        // Published images are public content addressed by media id, so
        // browsers may cache them for an hour (archival revokes access
        // within that window). The admin review route stays no-store.
        return $disk->response($media->getPathRelativeToRoot(),$media->file_name,[
            'Content-Type'=>$media->mime_type,'X-Content-Type-Options'=>'nosniff',
            'Cache-Control'=>$isReview ? 'private, no-store' : 'public, max-age=3600, must-revalidate',
            'Content-Security-Policy'=>"default-src 'none'; sandbox",
        ]);
    }
}
