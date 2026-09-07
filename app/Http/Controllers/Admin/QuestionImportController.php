<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        $headers = array_map(fn ($value) => strtolower(trim((string) $value)), fgetcsv($handle) ?: []);
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }

        if ($headers !== self::HEADERS) {
            fclose($handle);
            throw ValidationException::withMessages(['csv_file' => 'The CSV columns do not match the required template.']);
        }

        $rows = [];
        $errors = [];
        $seenQuestions = [];
        $line = 1;
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) continue;
            if (count($values) !== count(self::HEADERS)) {
                $errors[] = "Row {$line}: expected ".count(self::HEADERS).' columns.';
                continue;
            }
            $row = array_combine(self::HEADERS, array_map('trim', $values));
            $normalizedQuestion = mb_strtolower(preg_replace('/\s+/', ' ', $row['question']));
            if (isset($seenQuestions[$normalizedQuestion]) || Question::withTrashed()->whereRaw('LOWER(question) = ?', [mb_strtolower($row['question'])])->exists()) {
                $errors[] = "Row {$line}: the question already exists or is duplicated in this file.";
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
        fclose($handle);

        if ($errors !== []) {
            throw ValidationException::withMessages(['csv_file' => $errors]);
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['csv_file' => 'The CSV contains no question rows.']);
        }

        DB::transaction(fn () => collect($rows)->each(fn ($row) => Question::create($row)));
        return redirect()->route('allQuestion')->with('success', count($rows).' questions imported successfully.');
    }

    public function template()
    {
        return $this->csvResponse('question-import-template.csv', function ($stream) {
            fputcsv($stream, self::HEADERS);
            fputcsv($stream, ['What does a red traffic light mean?', 'Stop', 'Proceed', 'Turn only', 'Speed up', 'A', 'Traffic Rules', 'Easy', 'A red light requires drivers to stop.', 'Draft']);
        });
    }

    public function export()
    {
        return $this->csvResponse('questions-'.now()->format('Y-m-d').'.csv', function ($stream) {
            fputcsv($stream, self::HEADERS);
            Question::orderBy('id')->chunk(500, function ($questions) use ($stream) {
                foreach ($questions as $question) {
                    fputcsv($stream, [$question->question, $question->option1, $question->option2, $question->option3, $question->option4, $question->correctOption, $question->category, $question->difficulty, $question->explanation, $question->status]);
                }
            });
        });
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
