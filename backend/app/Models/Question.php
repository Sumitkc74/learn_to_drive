<?php

namespace App\Models;

use App\Models\Concerns\TracksCreator;
use App\Services\ContentReadiness;
use App\Support\QuestionReviewIndex;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Question extends Model implements HasMedia
{
    protected static function booted(): void
    {
        static::saving(function ($question) {
            if ($question->isDirty('question') || !$question->exists) {
                $question->language = preg_match('/\p{Devanagari}/u', $question->question ?? '') ? 'ne' : 'en';
            }
            $question->forceFill(QuestionReviewIndex::question($question->question ?? ''));
            if ($question->answer_review_hash && ! hash_equals($question->answer_review_hash, ContentReadiness::answerHash($question))) {
                $question->forceFill(['answer_verified_at' => null, 'answer_verified_by' => null, 'answer_review_hash' => null]);
            }
        });
    }

    use HasFactory, InteractsWithMedia, SoftDeletes, TracksCreator;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question',
        'option1',
        'option2',
        'option3',
        'option4',
        'correctOption',
        'category',
        'difficulty',
        'explanation',
        'status',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('question-images')->useDisk('protected-media');
    }

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('question-images') ?: null;
    }
}
