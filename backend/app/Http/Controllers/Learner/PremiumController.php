<?php
namespace App\Http\Controllers\Learner;
use App\Models\{User,PremiumChatTurn};
use App\Services\{PracticeRevision,LearnerChat};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PremiumController extends \App\Http\Controllers\Controller {
    public function index(){return view('learner.premium');}
    public function requestUpgrade(Request $request){
        if($request->user()->hasPremium())return $request->expectsJson()?response()->json(['message'=>__('Premium is active')]):redirect()->route('learn.premium');
        $ticket=DB::transaction(function()use($request){
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $existing=\App\Models\SupportTicket::where('user_id',$request->user()->id)->where('subject','Premium upgrade request')->whereIn('status',['New','In Progress'])->first();
            if($existing)return $existing;
            return \App\Models\SupportTicket::firstOrCreate([
                'user_id'=>$request->user()->id,'type'=>'Feature request','subject'=>'Premium upgrade request','status'=>'New',
            ],['message'=>'I would like Premium access to the study chatbot and personalized revision modules. Please let me know the upgrade arrangements.']);
        });
        if($request->expectsJson())return response()->json(['data'=>['ticket_id'=>$ticket->id],'message'=>__('Your Premium request is with the admin team. No payment has been taken.')]);
        return redirect()->route('learn.support.show',$ticket)->with('success',__('Your Premium request is with the admin team. No payment has been taken.'));
    }
    public function modules(Request $request,PracticeRevision $revision){
        $modules=$revision->questions($request->user()->id)->groupBy('category');
        return view('learner.revision',compact('modules'));
    }
    public function chat(Request $request,PracticeRevision $revision){
        $request->validate(['question_id'=>'nullable|integer']);
        $focus=$request->filled('question_id')?$revision->questions($request->user()->id)->firstWhere('id',(int)$request->question_id):null;
        if($request->filled('question_id'))abort_unless($focus,404);
        $turns=PremiumChatTurn::where('user_id',$request->user()->id)->latest('id')->limit(20)->get()->reverse();
        return view('learner.chat',compact('turns','focus'));
    }
    public function message(Request $request,LearnerChat $chat,PracticeRevision $revision){
        $data=$request->validate(['message'=>'required|string|max:2000','question_id'=>'nullable|integer']);
        $questions=$revision->questions($request->user()->id);
        if(!empty($data['question_id'])){
            $focus=$questions->firstWhere('id',(int)$data['question_id']);abort_unless($focus,404);
            $questions=collect([$focus]);
        }
        if(!config('pdf-translation.gemini_key'))return $request->expectsJson()?response()->json(['message'=>__('The study chatbot is not configured yet.')],503):back()->with('error',__('The study chatbot is not configured yet. Your revision modules are still available.'));
        $turn=DB::transaction(function()use($request,$data){
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $start=now('Asia/Kathmandu')->startOfDay()->setTimezone(config('app.timezone'));
            $count=PremiumChatTurn::where('user_id',$request->user()->id)->where('created_at','>=',$start)->count();
            if($count>=max(0,config('premium.daily_chat_limit')))throw \Illuminate\Validation\ValidationException::withMessages(['message'=>__('You have reached today’s chat limit. Try again tomorrow (Nepal time).')]);
            return PremiumChatTurn::create(['user_id'=>$request->user()->id,'message'=>$data['message']]);
        });
        try {
            $history=PremiumChatTurn::where('user_id',$request->user()->id)->where('status','Complete')->where('id','<',$turn->id)->latest('id')->limit(4)->get()->reverse();
            $answer=$chat->answer($data['message'],$history,$questions);
            $turn->update(['answer'=>$answer,'status'=>'Complete']);
        }catch(\Throwable $e){
            $turn->update(['status'=>'Failed']);
            if($request->expectsJson())return response()->json(['message'=>__('The chatbot could not respond. Please try again later.')],503);
            return redirect()->route('learn.premium.chat')->with('error',__('The chatbot could not respond. Please try again later.'));
        }
        if($request->expectsJson())return response()->json(['answer'=>$answer]);
        return redirect()->route('learn.premium.chat',array_filter(['question_id'=>$data['question_id'] ?? null]));
    }
}
