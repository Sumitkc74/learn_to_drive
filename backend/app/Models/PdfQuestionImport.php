<?php

namespace App\Models;

use App\Support\QuestionReviewIndex;
use Illuminate\Database\Eloquent\Model;

class PdfQuestionImport extends Model
{
    protected static function booted(): void
    {
        static::saving(function ($candidate) {
            $candidate->forceFill(QuestionReviewIndex::candidate($candidate->payload ?? [], $candidate->warnings ?? []));
        });
    }

    protected $guarded = ['id'];

    protected $casts = ['payload' => 'array', 'warnings' => 'array', 'reviewed_at' => 'datetime'];

    public function resource()
    {
        return $this->belongsTo(LearningContentImport::class, 'learning_content_import_id');
    }
}
