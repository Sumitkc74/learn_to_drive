<?php
namespace App\Http\Controllers\Learner;
use App\Models\SupportTicket;
use App\Support\ReportableContent;
use Illuminate\Http\Request;
class SupportController extends \App\Http\Controllers\Controller {
    public function index(Request $request) {
        $tickets=SupportTicket::where('user_id',$request->user()->id)->latest('id')->paginate(10);
        return view('learner.support',compact('tickets'));
    }
    public function store(Request $request) {
        $data=$request->validate(['type'=>'required|in:Complaint,Feature request,Other','subject'=>'required|string|max:200','message'=>'required|string|max:5000']);
        $ticket=SupportTicket::create($data+['user_id'=>$request->user()->id,'status'=>'New']);
        return redirect()->route('learn.support.show',$ticket)->with('success',__('Your message has been sent to the admin team.'));
    }
    public function show(Request $request,int $id) {
        $ticket=SupportTicket::where('user_id',$request->user()->id)->with('replies')->findOrFail($id);
        return view('learner.support-detail',compact('ticket'));
    }
    public function reply(Request $request,int $id) {
        $data=$request->validate(['message'=>'required|string|max:5000']);
        \Illuminate\Support\Facades\DB::transaction(function()use($request,$id,$data){
            $ticket=SupportTicket::where('user_id',$request->user()->id)->lockForUpdate()->findOrFail($id);
            $ticket->replies()->create($data+['user_id'=>$request->user()->id,'author_role'=>'User']);
            $ticket->update(['status'=>'New']);
        });
        return back()->with('success',__('Your reply has been sent.'));
    }
    public function reportForm(string $type,int $id) {
        $item=ReportableContent::visible($type,$id);abort_unless($item,404);
        $label=$item->{ReportableContent::TYPES[$type][2]};
        return view('learner.report',compact('type','id','label'));
    }
    public function report(Request $request,string $type,int $id) {
        $item=ReportableContent::visible($type,$id);abort_unless($item,404);
        $data=$request->validate(['message'=>'required|string|max:5000']);
        $ticket=SupportTicket::create($data+['user_id'=>$request->user()->id,'type'=>'Content report','subject'=>\Illuminate\Support\Str::limit('Report: '.$item->{ReportableContent::TYPES[$type][2]},190),'status'=>'New','content_type'=>$type,'content_id'=>$id]);
        return redirect()->route('learn.support.show',$ticket)->with('success',__('Thank you. The admin team can now review this content.'));
    }
}
