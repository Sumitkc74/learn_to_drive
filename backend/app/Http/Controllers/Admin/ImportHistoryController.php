<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ImportHistoryController extends \App\Http\Controllers\Controller {
    public function index(Request $request) {
        $query=DB::table('content_source_runs');
        if (in_array($request->query('status'),['Success','Failed','Running'])) $query->where('status',$request->query('status'));
        $runs=$query->orderByDesc('id')->paginate(25)->withQueryString();
        $usage=DB::table('content_ai_usage')->where('day',now('Asia/Kathmandu')->toDateString())->first();
        $latest=DB::table('content_source_runs')->whereIn('id',DB::table('content_source_runs')->selectRaw('MAX(id)')->groupBy('source'))->get();
        return view('admin.learning-content.history',compact('runs','usage','latest'));
    }
}
