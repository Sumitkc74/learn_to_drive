<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserHistory;
use Illuminate\Http\Request;

class UserHistoryController extends Controller
{
    public function recordHistory(Request $request)
    {
        return response()->json(['message'=>'This endpoint has been retired. Start and submit a practice session instead.'],410);
    }

    public function displayHistory(Request $request)
    {
        $request->validate(['page'=>['sometimes','integer','min:1']]);
        $page = UserHistory::where('user_id', $request->user()->id)->latest('id')->paginate(20);
        return response()->json(['status'=>true, 'data'=>[
            'userHistories'=>$page->items(),
            'pagination'=>['current_page'=>$page->currentPage(), 'last_page'=>$page->lastPage()],
        ]]);
    }
}
