<?php

namespace App\Services;

use App\Models\PdfQuestionImport;
use App\Models\Question;
use App\Support\QuestionReviewIndex;

class SimilarQuestions
{
    public function find(PdfQuestionImport $candidate): array
    {
        $normalized = QuestionReviewIndex::normalize($candidate->payload['question'] ?? '');
        if ($normalized === '') {
            return [];
        }
        $tokens = array_values(array_unique(preg_split('/\s+/u', $normalized)));
        $terms = array_values(array_filter($tokens, fn ($term) => mb_strlen($term) >= 3));
        usort($terms, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $terms = array_slice($terms, 0, 3);
        $matches = [];
        foreach (['question' => Question::withTrashed(), 'candidate' => PdfQuestionImport::where('id', '!=', $candidate->id)->where('status', 'Pending')] as $type => $query) {
            $exact = (clone $query)->where('question_hash', hash('sha256', $normalized))->limit(10)->get();
            $pool = collect();
            if (count($tokens) >= 4 && $terms) {
                $pool = (clone $query)->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('normalized_question', 'like', '%'.$term.'%');
                    }
                })->orderByDesc('id')->limit(200)->get();
            }
            foreach ($exact->merge($pool)->unique('id') as $item) {
                $other = $item->normalized_question ?? '';
                $otherTokens = array_values(array_unique(preg_split('/\s+/u', $other)));
                $score = $other === $normalized ? 100 : (int) round(200 * count(array_intersect($tokens, $otherTokens)) / max(1, count($tokens) + count($otherTokens)));
                if ($score < 75) {
                    continue;
                }
                $matches[] = ['type' => $type, 'id' => $item->id, 'question' => $type === 'question' ? $item->question : $item->payload['question'], 'score' => $score, 'exact' => $other === $normalized, 'bank' => $type === 'candidate' ? $item->learning_content_import_id : null];
            }
        }
        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($matches,0,8);
    }
}
