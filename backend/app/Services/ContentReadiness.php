<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;

class ContentReadiness
{
    public const LABELS = ['answer' => 'Incomplete answers', 'verification' => 'Answers not verified', 'image' => 'Missing sign/diagram images', 'explanation' => 'Missing explanations'];

    public static function answerHash(Question $question): string
    {
        $data = $question->only(['question', 'option1', 'option2', 'option3', 'option4', 'correctOption']);
        $media = $question->relationLoaded('media') ? $question->media : $question->media()->get();
        $data['images'] = $media->where('collection_name', 'question-images')->sortBy('id')->map(fn ($image) => $image->only(['id', 'file_name', 'size', 'updated_at']))->values()->all();

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function query(?string $issue = null): Builder
    {
        $query = Question::where('status', 'Draft');

        return match ($issue) {
            'answer' => $query->where(function ($q) {
                $q->whereNull('correctOption')->orWhereNotIn('correctOption', ['A', 'B', 'C', 'D']);
                foreach (['question', 'option1', 'option2', 'option3', 'option4'] as $field) {
                    $q->orWhereNull($field)->orWhereRaw("TRIM($field) = ''");
                }
                foreach ([[1, 2], [1, 3], [1, 4], [2, 3], [2, 4], [3, 4]] as [$a,$b]) {
                    $q->orWhereColumn('option'.$a, 'option'.$b);
                }
            }),
            'verification' => $query->whereNull('answer_verified_at'),
            'explanation' => $query->where(fn ($q) => $q->whereNull('explanation')->orWhereRaw("TRIM(explanation) = ''")),
            'image' => $query->where(function ($q) {
                $q->where('category', 'Road Signs')->orWhereExists(fn ($sub) => $sub->selectRaw('1')->from('pdf_question_imports')->whereColumn('question_id', 'questions.id')->where('needs_diagram', true));
            })->whereDoesntHave('media', fn ($q) => $q->where('collection_name', 'question-images')),
            default => $query,
        };
    }

    public function summary(): array
    {
        $counts = ['drafts' => $this->query()->count()];
        foreach (array_keys(self::LABELS) as $issue) {
            $counts[$issue] = $this->query($issue)->count();
        }

        return $counts;
    }
}
