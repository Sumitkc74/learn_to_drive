<?php
namespace App\Http\Controllers\Admin;
use App\Support\VersionedContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
class ContentVersionController extends \App\Http\Controllers\Controller {
    public function index(string $type,int $id) {
        abort_unless(isset(VersionedContent::TYPES[$type]),404);
        [$class,$edit]=VersionedContent::TYPES[$type]; $record=$class::findOrFail($id);
        $versions=DB::table('content_versions')->leftJoin('users','users.id','=','content_versions.actor_id')->where('content_type',$type)->where('content_id',$id)->select('content_versions.*','users.name as actor')->orderByDesc('content_versions.id')->paginate(10);
        return view('admin.workflow.versions',compact('record','versions','type','edit'));
    }
    public function restore(Request $request,string $type,int $id,int $version) {
        abort_unless(isset(VersionedContent::TYPES[$type]),404);
        $data=$request->validate(['confirmed'=>['accepted'],'current_hash'=>['required','string','size:64']]);
        [$class,$edit]=VersionedContent::TYPES[$type];
        DB::transaction(function() use($type,$id,$version,$class,$data){
            $record=$class::lockForUpdate()->findOrFail($id);
            abort_unless(hash_equals(VersionedContent::hash($record),$data['current_hash']),409,'Content changed. Reload before restoring.');
            $saved=DB::table('content_versions')->where('id',$version)->where('content_type',$type)->where('content_id',$id)->first(); abort_unless($saved,404);
            $values=array_intersect_key(json_decode($saved->snapshot,true),array_flip(VersionedContent::fields($record)));
            if(in_array('status',$record->getFillable())) $values['status']='Draft';
            if($record instanceof \App\Models\Question) $record->forceFill(['answer_verified_at'=>null,'answer_verified_by'=>null,'answer_review_hash'=>null]);
            $record->fill($values)->save();
        });
        return redirect()->route($edit,$id)->with('success','Earlier content restored. Current files were retained; review before publishing.');
    }
}
