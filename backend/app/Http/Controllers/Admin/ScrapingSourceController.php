<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
class ScrapingSourceController extends \App\Http\Controllers\Controller {
    public function index(\App\Services\ScrapingSourceSettings $settings) {
        $sources=$settings->all(); return view('admin.workflow.sources',compact('sources'));
    }
    public function update(Request $request,string $key,\App\Services\ScrapingSourceSettings $settings) {
        abort_unless(isset($settings->all()[$key]),404);
        $data=$request->validate(['enabled'=>['required','boolean'],'daily_time'=>['required','date_format:H:i']]);
        DB::table('scraping_sources')->updateOrInsert(['key'=>$key],$data+['updated_at'=>now(),'created_at'=>now()]);
        return back()->with('success','Source settings saved. Times use Nepal time.');
    }
}
