<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Support\SpreadsheetReader;
use App\Support\SpreadsheetDownload;
use App\Support\ImportSummary;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuestionImportController extends Controller
{
    private const HEADERS = ['question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option', 'category', 'difficulty', 'explanation', 'status'];

    public function create()
    {
        return view('admin.crud.questions.import');
    }

    public function store(Request $request)
    {
        $request->validate(['question_file' => ['required', 'file', 'extensions:csv,xlsx', 'mimetypes:text/plain,text/csv,application/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip', 'max:'.AppSetting::documentLimitKb()]]);
        $file = $request->file('question_file');
        try {
            $spreadsheetRows = SpreadsheetReader::read($file->getRealPath(), strtolower($file->getClientOriginalExtension()));
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['question_file' => $exception->getMessage()]);
        }
        $headers = array_map(fn ($value) => strtolower(trim((string) $value)), array_shift($spreadsheetRows) ?: []);
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }

        if ($headers !== self::HEADERS) {
            throw ValidationException::withMessages(['question_file' => 'The file columns do not match the required template.']);
        }

        $rows = [];
        $errors = [];
        $seenQuestions = [];
        $duplicates = 0;
        $blankRows = 0;
        $line = 1;
        foreach ($spreadsheetRows as $values) {
            $line++;
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) { $blankRows++; continue; }
            $values = array_pad($values, count(self::HEADERS), '');
            if (count($values) !== count(self::HEADERS)) {
                $errors[] = "Row {$line}: expected ".count(self::HEADERS).' columns.';
                continue;
            }
            $row = array_combine(self::HEADERS, array_map('trim', $values));
            $normalizedQuestion = mb_strtolower(preg_replace('/\s+/', ' ', $row['question']));
            if (isset($seenQuestions[$normalizedQuestion]) || Question::withTrashed()->whereRaw('LOWER(question) = ?', [mb_strtolower($row['question'])])->exists()) {
                $duplicates++;
                continue;
            }
            $validator = Validator::make($row, $this->rules());
            if ($validator->fails()) {
                $errors[] = "Row {$line}: ".$validator->errors()->first();
                continue;
            }
            $seenQuestions[$normalizedQuestion] = true;
            $rows[] = $this->map($validator->validated());
        }
        ImportSummary::finish('question_file', Question::class, $rows, $errors, $duplicates, $blankRows);
        return redirect()->route('allQuestion')->with('success', count($rows).' questions imported successfully.');
    }

    public function template()
    {
        return $this->csvResponse('question-import-template.csv', function ($stream) {
            fputcsv($stream, self::HEADERS);
            fputcsv($stream, ['What does a red traffic light mean?', 'Stop', 'Proceed', 'Turn only', 'Speed up', 'A', 'Traffic Rules', 'Easy', 'A red light requires drivers to stop.', 'Draft']);
        });
    }

    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');
        $rows = Question::orderBy('id')->cursor()->map(fn ($question) => [$question->question, $question->option1, $question->option2, $question->option3, $question->option4, $question->correctOption, $question->category, $question->difficulty, $question->explanation, $question->status]);
        return SpreadsheetDownload::make('questions-'.now()->format('Y-m-d'), self::HEADERS, $rows, $format);
    }

    private function rules(): array
    {
        return ['question' => ['required', 'string', 'max:500'], 'option_a' => ['required', 'string', 'max:255'], 'option_b' => ['required', 'string', 'max:255', 'different:option_a'], 'option_c' => ['required', 'string', 'max:255', 'different:option_a,option_b'], 'option_d' => ['required', 'string', 'max:255', 'different:option_a,option_b,option_c'], 'correct_option' => ['required', 'in:A,B,C,D'], 'category' => ['required', 'in:General,Road Signs,Traffic Rules,Road Safety,Vehicle Knowledge'], 'difficulty' => ['required', 'in:Easy,Medium,Hard'], 'explanation' => ['nullable', 'string', 'max:2000'], 'status' => ['required', 'in:Draft,Published,Archived']];
    }

    private function map(array $row): array
    {
        return ['question' => $row['question'], 'option1' => $row['option_a'], 'option2' => $row['option_b'], 'option3' => $row['option_c'], 'option4' => $row['option_d'], 'correctOption' => $row['correct_option'], 'category' => $row['category'], 'difficulty' => $row['difficulty'], 'explanation' => $row['explanation'] ?: null, 'status' => $row['status']];
    }

    private function csvResponse(string $filename, callable $writer)
    {
        return response()->streamDownload(function () use ($writer) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            $writer($stream);
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
