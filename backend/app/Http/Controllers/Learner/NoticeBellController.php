<?php
namespace App\Http\Controllers\Learner;
use App\Support\NoticeBell;
use Illuminate\Http\Request;

class NoticeBellController extends \App\Http\Controllers\Controller
{
    public function markAllRead(Request $request)
    {
        NoticeBell::markAllRead($request->user()->id);
        return back()->with('success', 'Notifications marked as read.');
    }
}
