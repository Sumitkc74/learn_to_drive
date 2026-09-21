<?php

namespace App\Support;

class QuestionReviewIndex
{
    public static function normalize(string $text): string
    {
        $text = strtr($text, array_combine(mb_str_split('०१२३४५६७८९'), str_split('0123456789')));

        return trim(preg_replace('/[^\p{L}\p{M}\p{N}]+/u', ' ', mb_strtolower($text)));
    }

    public static function question(string $text): array
    {
        $normalized = self::normalize($text);

        return ['normalized_question' => $normalized, 'question_hash' => hash('sha256', $normalized)];
    }

    public static function candidate(array $payload, array $warnings): array
    {
        $warnings = implode(' ', $warnings);

        return self::question($payload['question'] ?? '') + [
            'needs_answer' => empty($payload['correctOption']), 'needs_diagram' => str_contains($warnings, 'diagram'),
            'incomplete_options' => str_contains($warnings, 'Options are incomplete'), 'is_ocr' => ($payload['_extraction_method'] ?? '') === 'ocr',
        ];
    }
}
