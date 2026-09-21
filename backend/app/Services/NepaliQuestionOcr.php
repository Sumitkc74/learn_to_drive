<?php

namespace App\Services;

use App\Models\LearningContentImport;
use App\Models\PdfQuestionImport;
use RuntimeException;

class NepaliQuestionOcr
{
    public function __construct(private LocalPdfTools $tools) {}

    public function extract(LearningContentImport $resource, int $page): array
    {
        if ($page < 1 || $page > 1000) {
            throw new RuntimeException('Invalid PDF page.');
        }
        $result = $this->tools->ocr($resource, $page);
        $added = 0;
        $known = 0;
        $skipped = 0;
        foreach ($result['rows'] ?? [] as $row) {
            $parsed = $this->parseRow($row);
            if (! $parsed) {
                $skipped++;

                continue;
            }
            $key = hash('sha256', $resource->id.':'.$resource->file_hash.':'.$page.':ocr:'.$row['row']);
            $item = PdfQuestionImport::firstOrCreate(['fingerprint' => $key], $parsed + [
                'learning_content_import_id' => $resource->id, 'source_hash' => $resource->file_hash, 'page' => $page,
            ]);
            // Never replace text-extracted candidates or edits/decisions from previous OCR runs.
            $item->wasRecentlyCreated ? $added++ : $known++;
        }

        return compact('added', 'known', 'skipped') + ['pages' => $result['pages'] ?? $page];
    }

    public function parseRow(array $row): ?array
    {
        $text = trim($row['text']);
        // Unknown OCR labels stay in the raw text and are flagged rather than silently corrected.
        $parts = preg_split('/[（(]\h*([कखगघabcdABCD])\h*[)）]/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (count($parts) < 3) {
            return null;
        }
        $numberText = strtr(trim($row['number']), array_combine(mb_str_split('०१२३४५६७८९'), str_split('0123456789')));
        $hasNumber = preg_match('/^(\d{1,4})\s*[.।)]?$/u', $numberText, $match);
        $number = $hasNumber ? (int) $match[1] : (int) $row['row'];
        $payload = ['question' => $this->clean($parts[0]), 'correctOption' => '', 'category' => 'General', 'difficulty' => 'Medium', 'explanation' => '',
            '_extraction_method' => 'ocr', '_ocr_confidence' => (int) $row['confidence'], '_ocr_source_number' => trim($row['number']), '_ocr_row' => (int) $row['row']];
        if ($payload['question'] === '') {
            return null;
        }
        $warnings = ['OCR text: verify every word, number and option against the page preview. OCR confidence is not an accuracy guarantee.', 'Correct answer requires manual verification from the PDF. OCR does not interpret answer ticks.'];
        if (preg_match('/चित्र|चिन्ह|चिह्न|सङ्केत|संकेत|देखाइ|\b(sign|figure|picture|shown|signal)\b/ui', $payload['question'])) {
            $warnings[] = 'May require a diagram. Add the required image to the draft before publishing.';
        }
        if (! $hasNumber) {
            $warnings[] = 'Source question number was not recognized. The displayed number is the table row; locate it using the page preview.';
        }
        if ($row['confidence'] < 85) {
            $warnings[] = 'Low OCR confidence. This row needs careful correction.';
        }
        $labels = [];
        $map = ['क' => 1, 'ख' => 2, 'ग' => 3, 'घ' => 4, 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        for ($index = 1; $index + 1 < count($parts); $index += 2) {
            $option = $map[strtolower($parts[$index])];
            $labels[] = $option;
            $payload['option'.$option] = $this->clean($parts[$index + 1]);
        }
        foreach (range(1, 4) as $index) {
            $payload['option'.$index] ??= '';
        }
        if ($labels !== [1, 2, 3, 4] || in_array('', [$payload['option1'], $payload['option2'], $payload['option3'], $payload['option4']], true)) {
            $warnings[] = 'Options are incomplete or ambiguous. Correct their labels and text from the PDF.';
        }

        return ['number' => max(1, $number), 'payload' => $payload, 'raw_text' => mb_substr($text, 0, 15000), 'warnings' => $warnings];
    }

    private function clean(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
