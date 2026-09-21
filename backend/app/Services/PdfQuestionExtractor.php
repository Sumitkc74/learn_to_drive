<?php

namespace App\Services;

use App\Models\LearningContentImport;
use App\Models\PdfQuestionImport;
use App\Support\QuestionReviewIndex;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;

class PdfQuestionExtractor
{
    public function extract(LearningContentImport $resource, int $from, int $to): array
    {
        $disk = Storage::disk('learning-content');
        if ($resource->kind !== 'question-bank-document' || $resource->status === 'Rejected' || ! $resource->file_path || ! $disk->exists($resource->file_path)) {
            throw new RuntimeException('Save a private question-bank PDF before extracting. Rejected resources cannot be extracted.');
        }
        $path = $disk->path($resource->file_path);
        if (filesize($path) > 20 * 1024 * 1024 || ! hash_equals((string) $resource->file_hash, hash_file('sha256', $path))) {
            throw new RuntimeException('The saved PDF has changed or exceeds the 20 MB extraction limit.');
        }
        $config = new Config;
        $config->setRetainImageContent(false);
        $config->setDecodeMemoryLimit(32 * 1024 * 1024);
        $pages = (new Parser([], $config))->parseFile($path)->getPages();
        if ($from > count($pages)) {
            throw new RuntimeException('The starting page is beyond the end of this PDF.');
        }
        $added = 0;
        $known = 0;
        $empty = [];
        $answerHeaders = [];
        // Single-page background jobs need the same validated header context as a full run.
        for ($prior = 0; $prior < $from - 1; $prior++) {
            $this->withAnswerHeaders($pages[$prior]->getDataTm(), $answerHeaders);
        }
        for ($index = $from - 1; $index < min($to, count($pages)); $index++) {
            $text = $pages[$index]->getText();
            // Some rows end with their question on one page and options on the next.
            // Include only the continuation before the next numbered question, after its table header.
            if (isset($pages[$index + 1])) {
                $text .= "\n".$this->continuation($pages[$index + 1]->getText());
            }
            $positions = $this->withAnswerHeaders($pages[$index]->getDataTm(), $answerHeaders);
            $rows = $this->parsePage($text, $positions);
            if (! $rows) {
                $empty[] = $index + 1;
            }
            foreach ($rows as $row) {
                $key = hash('sha256', $resource->id.':'.$resource->file_hash.':'.($index + 1).':'.$row['number']);
                $item = PdfQuestionImport::firstOrCreate(['fingerprint' => $key], $row + [
                    'learning_content_import_id' => $resource->id, 'source_hash' => $resource->file_hash, 'page' => $index + 1,
                ]);
                if (! $item->wasRecentlyCreated) {
                    PdfQuestionImport::whereKey($item->id)->where('status', 'Pending')->update([
                        'payload' => json_encode($row['payload']), 'raw_text' => $row['raw_text'], 'warnings' => json_encode($row['warnings']),
                    ] + QuestionReviewIndex::candidate($row['payload'], $row['warnings']));
                }
                $item->wasRecentlyCreated ? $added++ : $known++;
            }
        }

        return compact('added', 'known', 'empty') + ['pages' => count($pages)];
    }

    // This profile handles numbered English DoTM tables. Unsupported layouts are reported, never guessed.
    public function withAnswerHeaders(array $positions, array &$previous): array
    {
        $headers = [];
        foreach ($positions as $position) {
            $label = strtoupper(trim($position[1]));
            if (in_array($label, ['A', 'B', 'C', 'D'], true) && $position[0][4] > 450) {
                $headers[$label] = $position;
            }
        }
        if ($headers) {
            // An incomplete or reordered header invalidates the preceding layout.
            $previous = [];
            if (count($headers) === 4) {
                $xs = array_map(fn ($letter) => $headers[$letter][0][4], ['A', 'B', 'C', 'D']);
                $ys = array_map(fn ($header) => $header[0][5], $headers);
                if (max($ys) - min($ys) < 3 && $xs[0] < $xs[1] && $xs[1] < $xs[2] && $xs[2] < $xs[3]) {
                    $previous = array_values($headers);
                }
            }

            return $positions;
        }

        // Continuation pages omit A–D headings. Tick X coordinates must still match
        // the preceding table columns and its question Y coordinate in parsePage.
        return array_merge($positions, $previous);
    }

    public function continuation(string $text): string
    {
        $prefix = preg_split('/^\h*\d{1,4}\.\s/mu', $text, 2)[0];

        // Keep only option text, excluding running titles, page numbers and table headings.
        return preg_match('/^\h*\(?[a-d]\).*\z/msiu', $prefix, $match) ? trim($match[0]) : '';
    }

    public function parsePage(string $text, array $positions): array
    {
        preg_match_all('/^\h*(\d{1,4})\.\s+(.*?)(?=^\h*\d{1,4}\.\s|\z)/msu', $text, $blocks, PREG_SET_ORDER);
        $columns = [];
        $numbers = [];
        $ticks = [];
        foreach ($positions as $position) {
            [$matrix, $value] = $position;
            $value = trim($value);
            $x = (float) $matrix[4];
            $y = (float) $matrix[5];
            if (preg_match('/^[abcd]$/i', $value) && $x > 450) {
                $columns[strtoupper($value)] = [$x, $y];
            }
            if (preg_match('/^(\d{1,4})\.(?:\s|$)/', $value, $match) && $x < 120) {
                $numbers[(int) $match[1]] = $y;
            }
            if (in_array($value, ['√', '✓', '✔'], true)) {
                $ticks[] = [$x, $y];
            }
        }
        $validColumns = count($columns) === 4;
        if ($validColumns) {
            $xs = array_map(fn ($letter) => $columns[$letter][0], ['A', 'B', 'C', 'D']);
            $ys = array_column($columns, 1);
            $validColumns = max($ys) - min($ys) < 3 && $xs[0] < $xs[1] && $xs[1] < $xs[2] && $xs[2] < $xs[3];
        }
        $rows = [];
        foreach ($blocks as $block) {
            $number = (int) $block[1];
            $raw = trim($block[2]);
            $clean = trim(preg_replace('/[√✓✔]/u', '', $raw));
            $parts = preg_split('/(?<!\w)\(?([a-d])\)/i', $clean, -1, PREG_SPLIT_DELIM_CAPTURE);
            if (count($parts) < 3 && ! str_contains($clean, '?')) {
                continue;
            }
            $payload = ['question' => $this->clean($parts[0]), 'correctOption' => '', 'category' => 'General', 'difficulty' => 'Medium', 'explanation' => ''];
            $warnings = ['Compare the question, all options and the suggested answer with the original PDF.'];
            $labels = [];
            for ($i = 1; $i + 1 < count($parts); $i += 2) {
                $label = strtoupper($parts[$i]);
                $labels[] = $label;
                $payload['option'.(ord($label) - 64)] = $this->clean($parts[$i + 1]);
            }
            foreach (range(1, 4) as $i) {
                $payload['option'.$i] ??= '';
            }
            if ($labels !== ['A', 'B', 'C', 'D'] || in_array('', array_intersect_key($payload, array_flip(['option1', 'option2', 'option3', 'option4'])), true)) {
                $warnings[] = 'Options are incomplete or ambiguous. Correct them from the PDF before import.';
            }
            $matches = [];
            if ($validColumns && isset($numbers[$number])) {
                foreach ($ticks as [$x, $y]) {
                    if (abs($y - $numbers[$number]) > 4) {
                        continue;
                    }
                    foreach ($columns as $letter => [$columnX]) {
                        if (abs($x - $columnX) < 4) {
                            $matches[] = $letter;
                        }
                    }
                }
            }
            if (count($matches) === 1) {
                $payload['correctOption'] = $matches[0];
            } else {
                $warnings[] = 'Correct answer could not be identified reliably. Select it after checking the PDF.';
            }
            if (preg_match('/\b(sign|figure|picture|shown|signal)\b/i', $payload['question'])) {
                $warnings[] = 'May require a diagram. Images are not extracted; add the required image to the draft before publishing.';
            }
            $rows[] = ['number' => $number, 'payload' => $payload, 'raw_text' => mb_substr($raw, 0, 15000), 'warnings' => $warnings];
        }

        return $rows;
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value));
    }
}
