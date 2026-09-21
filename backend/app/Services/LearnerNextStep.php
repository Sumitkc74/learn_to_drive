<?php
namespace App\Services;

use App\Models\LearnerPracticeAttempt;
use App\Models\Question;

class LearnerNextStep
{
    public function forUser(int $userId): array
    {
        $pending=LearnerPracticeAttempt::where('user_id',$userId)->whereNull('completed_at')->where('expires_at','>',now())->latest('id')->limit(10)->get();
        foreach($pending as $attempt) {
            $questions=Question::where('status','Published')->whereIn('id',array_column($attempt->questions,'id'))->get()->keyBy('id');
            $valid=collect($attempt->questions)->every(function($saved)use($questions){
                $question=$questions->get($saved['id']);
                return $question && hash_equals($saved['hash'],hash('sha256',json_encode($question->only(['question','option1','option2','option3','option4','correctOption','explanation']))));
            });
            if($valid) return ['action'=>'resume','attempt_id'=>$attempt->id,'label'=>'Resume your practice','reason'=>'Your unfinished session is ready. Continue before it expires.','url'=>route('learn.practice.attempt',$attempt)];
        }
        $mistakes=app(PracticeRevision::class)->questions($userId);
        $topic=$mistakes->groupBy('category')->sortByDesc(fn($questions)=>$questions->count())->keys()->first();
        if($topic!==null) return ['action'=>'practice','category'=>$topic,'label'=>'Practise your difficult topic','reason'=>'Focus on :topic after your recent mistakes.','topic'=>__(preg_replace('/(?<!^)[A-Z]/',' $0',$topic)),'url'=>route('learn.practice',['category'=>$topic])];
        if(Question::where('status','Published')->exists()) return ['action'=>'practice','label'=>'Start a short practice','reason'=>'Build confidence with a few questions and review your answers.','url'=>route('learn.practice')];
        return ['action'=>'flashcards','label'=>'Try sign flashcards','reason'=>'Learn a few road signs while more questions are being prepared.','url'=>route('learn.flashcards')];
    }
}
