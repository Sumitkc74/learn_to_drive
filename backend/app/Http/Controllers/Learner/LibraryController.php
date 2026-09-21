<?php
namespace App\Http\Controllers\Learner;
use App\Models\{Question,TrafficSign,VisionTest,ExamPaper,ExamInformation,Tutorial,Notice,LearnerPracticeAttempt};
use Illuminate\Http\Request;
class LibraryController extends \App\Http\Controllers\Controller {
    public const TYPES=[
        'traffic-sign'=>[TrafficSign::class,'Traffic signs','name','Learn the signs before you meet them on the road.'],
        'question-bank'=>[ExamPaper::class,'Question banks','name','Download question collections in English or Nepali.'],
        'exam-information'=>[ExamInformation::class,'Exam information','name','Find guidance and documents for your next step.'],
        'tutorial'=>[Tutorial::class,'Tutorials','title','Build your understanding, one lesson at a time.'],
        'vision-test'=>[VisionTest::class,'Vision practice','testNumber','Explore visual recognition exercises for learning.'],
        'notice'=>[Notice::class,'Notices','title','Keep up with current updates and announcements.'],
    ];
    public function home(){
        if(auth()->check())return redirect()->route('learn.account');
        $counts=['questions'=>Question::where('status','Published')->count(),'signs'=>TrafficSign::count(),'banks'=>ExamPaper::count()];
        return view('learner.home',compact('counts'));
    }
    public function flashcards(){
        $signs=TrafficSign::with('media')->whereHas('media')->orderBy('id')->simplePaginate(1);
        return view('learner.flashcards',compact('signs'));
    }
    public function index(Request $request,string $type){
        abort_unless(isset(self::TYPES[$type]),404);[$class,$title,$field,$intro]=self::TYPES[$type];
        $data=$request->validate(['q'=>['nullable','string','max:100'],'language'=>['nullable','in:English,Nepali']]);
        $query=$class::query();if($type==='notice') $query->visibleToLearners();else $query->with('media');
        if(!empty($data['q'])) $query->where($field,'like','%'.$data['q'].'%');
        if(in_array($type,['question-bank','exam-information']) && !empty($data['language'])) $query->where('language',$data['language']);
        $items=$query->latest('id')->paginate(12)->withQueryString();
        return view('learner.library',compact('type','title','field','intro','items'));
    }
    public function show(string $type,int $id){
        abort_unless(isset(self::TYPES[$type]),404);[$class,$title,$field,$intro]=self::TYPES[$type];
        $query=$class::query();if($type==='notice')$query->visibleToLearners();else $query->with('media');
        $item=$query->findOrFail($id);return view('learner.detail',compact('type','title','field','item'));
    }
    public function account(Request $request){
        $progress=app(\App\Services\LearnerProgress::class)->forUser($request->user()->id);
        $attempts=LearnerPracticeAttempt::where('user_id',$request->user()->id)->whereNotNull('completed_at')->latest('id')->paginate(10);
        $total=LearnerPracticeAttempt::where('user_id',$request->user()->id)->whereNotNull('completed_at')->count();
        $nextStep=app(\App\Services\LearnerNextStep::class)->forUser($request->user()->id);
        return view('learner.account',compact('attempts','total','progress','nextStep'));
    }
}
