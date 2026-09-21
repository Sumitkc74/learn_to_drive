<?php
namespace App\Http\Controllers\Learner;
use App\Models\{Question,LearnerPracticeAttempt,UserHistory};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PracticeController extends \App\Http\Controllers\Controller {
    private const FIELDS=['question','option1','option2','option3','option4','correctOption','explanation'];
    public function index(){
        $categories=Question::where('status','Published')->select('category')->distinct()->orderBy('category')->pluck('category');
        $count=Question::where('status','Published')->count();return view('learner.practice-start',compact('categories','count'));
    }
    public function start(Request $request){
        $data=$request->validate(['category'=>['nullable','string','max:100'],'count'=>['required','in:5,10,20,25'],'mode'=>['nullable','in:revision,personalized,mock'],'language'=>['nullable','in:en,ne']]);
        $data['language']=$data['language'] ?? app()->getLocale();
        if($request->hasSession() && $request->has('language'))$request->session()->put('practice_language',$data['language'] ?? '');
        if(($data['mode'] ?? '')==='mock')$data['count']=25;
        $query=Question::where('status','Published')->whereIn('correctOption',['A','B','C','D']);
        if(!empty($data['language']))$query->where('language',$data['language']);
        if(in_array($request->input('mode'),['revision','personalized'])){
            abort_unless($request->user()->hasPremium(),403);
            $query->whereIn('id',app(\App\Services\PracticeRevision::class)->questions($request->user()->id)->pluck('id'));
        }
        if(!empty($data['category']))$query->where('category',$data['category']);
        $questions=$query->inRandomOrder()->limit((int)$data['count'])->get();
        if($request->input('mode')==='personalized')$questions=app(\App\Services\PracticeRevision::class)->personalized($request->user()->id,(int)$data['count'],$data['category'] ?? null,$data['language'] ?? null);
        if($questions->isEmpty() && $request->expectsJson())throw \Illuminate\Validation\ValidationException::withMessages(['practice'=>__('No questions match this topic and language. Try another selection.')]);
        if($questions->isEmpty() && in_array($request->input('mode'),['revision','personalized']))return redirect()->route('learn.premium.modules')->with('success',__('No current mistakes to practise in this topic. Complete a regular session to update your recommendations.'));
        if($questions->isEmpty())return back()->withErrors(['practice'=>__('No questions match this topic and language. Try another selection.')])->withInput();
        $snapshot=$questions->map(fn($q)=>['id'=>$q->id,'hash'=>hash('sha256',json_encode($q->only(self::FIELDS))),'text'=>$q->question,'options'=>['A'=>$q->option1,'B'=>$q->option2,'C'=>$q->option3,'D'=>$q->option4],'correct'=>$q->correctOption,'explanation'=>$q->explanation])->all();
        $attempt=LearnerPracticeAttempt::create(['user_id'=>$request->user()->id,'questions'=>$snapshot,'mode'=>$data['mode'] ?? 'practice','expires_at'=>($data['mode'] ?? '')==='mock'?now()->addMinutes(30):now()->addHours(2)]);
        if($request->expectsJson())return $this->show($request,$attempt->id);
        return redirect()->route('learn.practice.attempt',$attempt);
    }
    private function current($attempt){
        $questions=Question::where('status','Published')->whereIn('id',array_column($attempt->questions,'id'))->get()->keyBy('id');
        foreach($attempt->questions as $saved){$q=$questions->get($saved['id']);abort_unless($q && hash_equals($saved['hash'],hash('sha256',json_encode($q->only(self::FIELDS)))),409,__('A question changed. Please start a new practice session.'));}
        return $questions;
    }
    public function show(Request $request,int $id){
        $attempt=LearnerPracticeAttempt::where('user_id',$request->user()->id)->findOrFail($id);
        if(!$attempt->completed_at && $attempt->mode==='mock' && $attempt->expires_at->isPast()) {
            DB::transaction(function()use($attempt){
                $locked=LearnerPracticeAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
                if(!$locked->completed_at){$this->current($locked);$this->complete($locked,$locked->answers ?? [],true);}
            });
            $attempt->refresh();
        }
        if(!$attempt->completed_at)abort_if($attempt->expires_at->isPast(),410,__('This practice session expired. Start a new session.'));
        $questions=$this->current($attempt);
        if($request->expectsJson())return response()->json(['data'=>[
            'id'=>$attempt->id,'mode'=>$attempt->mode,'questions'=>collect($attempt->questions)->map(fn($q)=>array_merge($attempt->completed_at?$q:collect($q)->except(['correct','explanation','hash'])->all(),['image_url'=>$questions[$q['id']]->image_url]))->values(),
            'answers'=>(object)($attempt->answers ?? []),'score'=>$attempt->completed_at?$attempt->score:null,
            'completed_at'=>$attempt->completed_at,'expires_at'=>$attempt->expires_at,'server_time'=>now()->toIso8601String(),
        ]]);
        return view('learner.practice',compact('attempt','questions'));
    }
    public function submit(Request $request,int $id){
        $data=$request->validate(['answers'=>['required','array','max:25'],'answers.*'=>['required','in:A,B,C,D']]);
        DB::transaction(function()use($request,$id,$data){
            $attempt=LearnerPracticeAttempt::where('user_id',$request->user()->id)->lockForUpdate()->findOrFail($id);
            if($attempt->completed_at)return;
            abort_if($attempt->expires_at->isPast(),410,__('This practice session expired.'));$this->current($attempt);
            $this->complete($attempt,$data['answers']);
        });
        if($request->expectsJson())return $this->show($request,$id);
        return redirect()->route('learn.practice.attempt',$id);
    }

    private function complete($attempt,array $submitted,bool $allowUnanswered=false): void
    {
        $answers=[];$score=0;
        foreach($attempt->questions as $q){
            if(!isset($submitted[$q['id']]) && !$allowUnanswered)throw \Illuminate\Validation\ValidationException::withMessages(['answers'=>__('Answer every question before submitting.')]);
            $answer=$submitted[$q['id']] ?? '';$answers[$q['id']]=$answer;$score+=(int)($answer===$q['correct']);
        }
        $attempt->update(['answers'=>$answers,'score'=>$score,'completed_at'=>now()]);
        $payload=['user_id'=>$attempt->user_id,'attempted_questions'=>json_encode(array_column($attempt->questions,'text')),'correct_options'=>json_encode(array_column($attempt->questions,'correct')),'selected_options'=>json_encode(array_values($answers))];
        foreach(['A','B','C','D'] as $letter)$payload['option'.$letter]=json_encode(array_map(fn($q)=>$q['options'][$letter],$attempt->questions));
        UserHistory::create($payload);
    }

    public function saveDraft(Request $request,int $id)
    {
        $data=$request->validate(['answers'=>['nullable','array','max:25'],'answers.*'=>['required','in:A,B,C,D']]);
        DB::transaction(function()use($request,$id,$data){
            $attempt=LearnerPracticeAttempt::where('user_id',$request->user()->id)->lockForUpdate()->findOrFail($id);
            abort_if($attempt->completed_at,409);
            abort_if($attempt->expires_at->isPast(),410);
            $this->current($attempt);
            $answers=$data['answers'] ?? [];
            abort_if(array_diff(array_keys($answers),array_column($attempt->questions,'id')),422);
            $attempt->update(['answers'=>array_replace($attempt->answers ?? [],$answers)]);
        });
        return $request->expectsJson() ? response()->json(['message'=>__('Answers saved.')])
            : redirect()->route('learn.account')->with('success',__('Answers saved.'));
    }
}
