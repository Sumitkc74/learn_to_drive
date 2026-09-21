<?php
namespace App\Http\Controllers\Learner;

use App\Models\LearnerBookmark;
use App\Support\ReportableContent;
use Illuminate\Http\Request;

class BookmarkController extends \App\Http\Controllers\Controller
{
    public function index(Request $request)
    {
        $bookmarks = LearnerBookmark::where('user_id',$request->user()->id)->latest('id')->paginate(12);
        $resources = $bookmarks->getCollection()->mapWithKeys(fn($bookmark)=>[
            $bookmark->id=>ReportableContent::visible($bookmark->content_type,$bookmark->content_id),
        ]);
        return view('learner.bookmarks',compact('bookmarks','resources'));
    }
    public function store(Request $request, string $type, int $id)
    {
        abort_unless(isset(LibraryController::TYPES[$type]) && ReportableContent::visible($type,$id),404);
        LearnerBookmark::firstOrCreate(['user_id'=>$request->user()->id,'content_type'=>$type,'content_id'=>$id]);
        return back()->with('success',__('Resource saved.'));
    }
    public function destroy(Request $request, int $id)
    {
        LearnerBookmark::where('user_id',$request->user()->id)->findOrFail($id)->delete();
        return back()->with('success',__('Saved resource removed.'));
    }
}
