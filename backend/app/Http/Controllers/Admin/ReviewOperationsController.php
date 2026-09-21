<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
class ReviewOperationsController extends \App\Http\Controllers\Controller {
    public const TYPES=['learning'=>\App\Models\LearningContentImport::class,'notices'=>\App\Models\Notice::class,'government'=>\App\Models\GovernmentNoticeImport::class];
    public function index(Request $request) {
        $type=$request->query('type','learning'); abort_unless(isset(self::TYPES[$type]),404);
        $class=self::TYPES[$type]; $items=$class::where('status',$type==='notices'?'Published':'Pending')->orderBy('id')->paginate(25)->withQueryString();
        $admins=\App\Models\User::where('role','Admin')->get(['id','name']);
        return view('admin.workflow.review',compact('type','items','admins'));
    }
    public function update(Request $request) {
        $data=$request->validate(['type'=>['required','in:learning,notices,government'],'ids'=>['required','array','min:1','max:25'],'ids.*'=>['integer','distinct'],
            'action'=>['required','in:reject,category,archive,assign,claim,release'],'confirmed'=>['accepted'],'reason'=>['required_if:action,reject','nullable','string','max:1000'],
            'category'=>['required_if:action,category','nullable','in:A/K,B,All,Other,Unknown'],'admin_id'=>['required_if:action,assign','nullable','integer']]);
        $type=$data['type']; $action=$data['action'];
        abort_unless(($type==='notices' && $action==='archive') || ($type!=='notices' && in_array($action,['reject','assign','claim','release'])) || ($type==='learning' && $action==='category'),422);
        if($action==='assign') abort_unless(\App\Models\User::where('role','Admin')->whereKey($data['admin_id'])->exists(),422);
        DB::transaction(function() use($data,$type,$action){
            $class=self::TYPES[$type]; $items=$class::whereIn('id',$data['ids'])->orderBy('id')->lockForUpdate()->get();
            abort_unless($items->count()===count($data['ids']),409,'An item is no longer available.');
            foreach($items as $item) {
                abort_unless($item->status===($type==='notices'?'Published':'Pending'),409,'An item was already reviewed. Reload the queue.');
                if($type!=='notices' && $action!=='assign') abort_if($item->assigned_to && $item->assigned_to!==auth()->id(),409,'An item is assigned to another admin.');
                $values=match($action){
                    'archive'=>['status'=>'Archived'],
                    'category'=>['licence_category'=>$data['category']],
                    'claim'=>['assigned_to'=>auth()->id(),'claimed_at'=>now()],
                    'release'=>['assigned_to'=>null,'claimed_at'=>null],
                    'assign'=>['assigned_to'=>$data['admin_id'],'claimed_at'=>now()],
                    default=>['status'=>'Rejected','reviewed_by'=>auth()->id(),'reviewed_at'=>now()],
                };
                if($action==='reject' && $type==='learning') $values['review_notes']=$data['reason'];
                $item->forceFill($values)->save();
                \App\Models\AuditLog::create(['actor_id'=>auth()->id(),'event'=>'bulk_'.$action,'subject_type'=>class_basename($item),'subject_id'=>$item->id,'subject_label'=>$item->title,'old_values'=>[],'new_values'=>['reason'=>$data['reason']??null]+$values]);
            }
        });
        return back()->with('success',count($data['ids']).' items updated. Nothing was published.');
    }
}
