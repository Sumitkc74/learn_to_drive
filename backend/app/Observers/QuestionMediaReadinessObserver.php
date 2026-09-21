<?php

namespace App\Observers;

use App\Models\Question;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class QuestionMediaReadinessObserver
{
    public function saved(Media $media): void
    {
        $this->invalidate($media);
    }

    public function deleted(Media $media): void
    {
        $this->invalidate($media);
    }

    private function invalidate(Media $media): void
    {
        if ($media->collection_name !== 'question-images' || $media->model_type !== (new Question)->getMorphClass()) {
            return;
        }
        $question = Question::find($media->model_id);
        if ($question?->answer_verified_at) {
            $question->forceFill(['answer_verified_at' => null, 'answer_verified_by' => null, 'answer_review_hash' => null])->save();
        }
    }
}
