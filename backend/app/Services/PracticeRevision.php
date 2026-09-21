<?php
namespace App\Services;
use App\Models\{Question,LearnerPracticeAttempt};
class PracticeRevision {
    public function personalized(int $userId,int $count,?string $category=null,?string $language=null) {
        $mistakes=$this->questions($userId);
        if($language)$mistakes=$mistakes->where('language',$language);
        if($category)$mistakes=$mistakes->where('category',$category);
        if($mistakes->isEmpty())return collect();
        $selected=$mistakes->shuffle()->take((int)ceil($count/2));
        $related=Question::where('status','Published')->whereIn('correctOption',['A','B','C','D'])
            ->when($language,fn($query)=>$query->where('language',$language))
            ->whereIn('category',$mistakes->pluck('category')->unique())
            ->whereNotIn('id',$mistakes->pluck('id'))->inRandomOrder()->limit($count-$selected->count())->get();
        return $selected->concat($related)->concat($mistakes->whereNotIn('id',$selected->pluck('id'))->shuffle())
            ->unique('id')->take($count)->shuffle()->values();
    }
    public function questions(int $userId) {
        $latest=[];
        foreach(LearnerPracticeAttempt::where('user_id',$userId)->whereNotNull('completed_at')->orderByDesc('completed_at')->orderByDesc('id')->limit(100)->get() as $attempt) {
            foreach($attempt->questions as $saved) {
                if(!isset($latest[$saved['id']]))$latest[$saved['id']]=['saved'=>$saved,'answer'=>$attempt->answers[$saved['id']] ?? null];
            }
        }
        return Question::with('media')->where('status','Published')->whereIn('id',array_keys($latest))->get()->filter(function($question)use($latest){
            $item=$latest[$question->id];
            $hash=hash('sha256',json_encode($question->only(['question','option1','option2','option3','option4','correctOption','explanation'])));
            return isset($item['saved']['hash']) && hash_equals($item['saved']['hash'],$hash) && $item['answer']!==$question->correctOption;
        });
    }
}
