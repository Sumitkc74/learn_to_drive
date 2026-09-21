<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Learner\LibraryController;
use App\Models\{LearnerBookmark,LearnerPracticeAttempt,Question,PremiumChatTurn};
use App\Services\{LearnerProgress,PracticeRevision};
use App\Support\{LearnerContent,ReportableContent};
use Illuminate\Http\Request;

class LearnerController extends Controller
{
    public function catalog(Request $request)
    {
        return response()->json(['data'=>[
            'types'=>collect(LibraryController::TYPES)->map(fn($entry,$type)=>['type'=>$type,'title'=>__($entry[1])])->values(),
            'categories'=>Question::where('status','Published')->distinct()->orderBy('category')->pluck('category'),
            'languages'=>['en','ne'],
            'premium'=>['active'=>(bool)($request->user('sanctum')?->is_active && $request->user('sanctum')?->hasPremium()),'amount_paisa'=>config('payments.amount_paisa'),'days'=>config('payments.days'),'daily_chat_limit'=>config('premium.daily_chat_limit')],
        ]]);
    }

    public function resource(string $type, $item): array
    {
        $field=LibraryController::TYPES[$type][2];
        return [
            'bookmark_id'=>($user=request()->user('sanctum'))?LearnerBookmark::where('user_id',$user->id)->where('content_type',$type)->where('content_id',$item->id)->value('id'):null,
            'id'=>$item->id,'type'=>$type,'title'=>LearnerContent::text($item,$field),
            'description'=>LearnerContent::text($item,'description'),
            'language'=>$item->language ?? null,'file_type'=>$item->file_type ?? null,
            'media_url'=>method_exists($item,'getFirstMediaUrl')?$item->getFirstMediaUrl():null,
            'external_url'=>$type==='tutorial'?$item->videoLink:($type==='notice'?$item->link:null),
        ];
    }

    public function library(Request $request, string $type)
    {
        abort_unless(isset(LibraryController::TYPES[$type]),404);
        [$class,,$field]=LibraryController::TYPES[$type];
        $data=$request->validate(['q'=>'nullable|string|max:100','language'=>'nullable|in:English,Nepali','page'=>'sometimes|integer|min:1','flashcards'=>'sometimes|boolean']);
        $query=$class::query();
        if($type==='notice')$query->visibleToLearners();else $query->with('media');
        if(!empty($data['q']))$query->where($field,'like','%'.$data['q'].'%');
        if(in_array($type,['question-bank','exam-information']) && !empty($data['language']))$query->where('language',$data['language']);
        if($type==='traffic-sign' && $request->boolean('flashcards'))$query->whereHas('media');
        $page=$query->latest('id')->paginate(12);
        return response()->json(['data'=>$page->getCollection()->map(fn($item)=>$this->resource($type,$item)),
            'next_page'=>$page->hasMorePages()?$page->currentPage()+1:null]);
    }

    public function detail(string $type,int $id)
    {
        abort_unless(isset(LibraryController::TYPES[$type]),404);
        $item=ReportableContent::visible($type,$id);abort_unless($item,404);
        return response()->json(['data'=>$this->resource($type,$item)]);
    }

    public function account(Request $request, LearnerProgress $progress)
    {
        $user=$request->user();
        $attempts=LearnerPracticeAttempt::where('user_id',$user->id)->latest('id')->paginate(10);
        return response()->json(['data'=>[
            'user'=>$user->only(['id','name','email','phoneNumber','email_verified_at']),
            'premium'=>$user->hasPremium(),'mfa_enabled'=>(bool)$user->mfa_secret,
            'progress'=>$progress->forUser($user->id),
            'next_step'=>app(\App\Services\LearnerNextStep::class)->forUser($user->id),
            'attempts'=>$attempts->getCollection()->map(fn($a)=>['id'=>$a->id,'score'=>$a->score,'total'=>count($a->questions),'completed_at'=>$a->completed_at,'expires_at'=>$a->expires_at,'status'=>$a->completed_at?'completed':($a->expires_at->isPast()?($a->mode==='mock'?'results_pending':'expired'):'active')]),
            'next_page'=>$attempts->hasMorePages()?$attempts->currentPage()+1:null,
        ]]);
    }

    public function saved(Request $request)
    {
        $page=LearnerBookmark::where('user_id',$request->user()->id)->latest('id')->paginate(12);
        return response()->json(['data'=>$page->getCollection()->map(function($bookmark){
            $item=ReportableContent::visible($bookmark->content_type,$bookmark->content_id);
            return ['bookmark_id'=>$bookmark->id,'resource'=>$item?$this->resource($bookmark->content_type,$item):null];
        }), 'next_page'=>$page->hasMorePages()?$page->currentPage()+1:null]);
    }

    public function save(Request $request,string $type,int $id)
    {
        abort_unless(isset(LibraryController::TYPES[$type]) && ReportableContent::visible($type,$id),404);
        $bookmark=LearnerBookmark::firstOrCreate(['user_id'=>$request->user()->id,'content_type'=>$type,'content_id'=>$id]);
        return response()->json(['data'=>['bookmark_id'=>$bookmark->id],'message'=>__('Resource saved.')]);
    }

    public function unsave(Request $request,int $id)
    {
        LearnerBookmark::where('user_id',$request->user()->id)->findOrFail($id)->delete();
        return response()->json(['message'=>__('Saved resource removed.')]);
    }

    public function revision(Request $request,PracticeRevision $revision)
    {
        abort_unless($request->user()->hasPremium(),403);
        return response()->json(['data'=>$revision->questions($request->user()->id)->map(fn($q)=>$q->only(['id','question','category','explanation','correctOption','option1','option2','option3','option4','image_url']))->values()]);
    }

    public function chat(Request $request)
    {
        abort_unless($request->user()->hasPremium(),403);
        return response()->json(['data'=>PremiumChatTurn::where('user_id',$request->user()->id)->latest('id')->limit(20)->get(['id','message','answer','status'])->reverse()->values()]);
    }
    public function payments(Request $request)
    {
        $page=\App\Models\PremiumPayment::where('user_id',$request->user()->id)->latest()->paginate(10);
        return response()->json(['data'=>$page->getCollection()->map(fn($p)=>$p->only(['id','gateway','amount_paisa','status','paid_at','access_until','created_at'])),'next_page'=>$page->hasMorePages()?$page->currentPage()+1:null]);
    }
    public function verifyPayment(Request $request,string $id,\App\Services\PremiumGateway $gateway)
    {
        $payment=\App\Models\PremiumPayment::where('user_id',$request->user()->id)->findOrFail($id);
        try{$paid=$gateway->verify($payment);}catch(\Throwable $e){return response()->json(['message'=>'Payment could not be verified yet. If money was deducted, do not pay again; contact support.'],503);}
        return response()->json(['message'=>$paid?'Payment verified. Premium access is active.':'Payment is not confirmed yet. Check again later if money was deducted.']);
    }

}
