<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Support\SpreadsheetReader;
use App\Support\SpreadsheetDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NoticeImportController extends Controller
{
    private const HEADERS = ['title', 'description', 'nepali_title', 'nepali_description', 'link', 'status', 'publish_at', 'expires_at'];

    public function create()
    {
        return view('admin.crud.notices.import');
    }

    public function store(Request $request)
    {
        $request->validate(['notice_file' => ['required', 'file', 'extensions:csv,xlsx', 'mimetypes:text/plain,text/csv,application/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip', 'max:2048']]);
        $file = $request->file('notice_file');

        try {
            $spreadsheetRows = SpreadsheetReader::read($file->getRealPath(), strtolower($file->getClientOriginalExtension()));
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['notice_file' => $exception->getMessage()]);
        }

        $headers = array_map(fn ($value) => strtolower(trim((string) $value)), array_shift($spreadsheetRows) ?: []);
        if (isset($headers[0])) $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        if ($headers !== self::HEADERS) throw ValidationException::withMessages(['notice_file' => 'The file columns do not match the notice template.']);

        $rows = [];
        $errors = [];
        $seen = [];
        foreach ($spreadsheetRows as $index => $values) {
            $line = $index + 2;
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) continue;
            if (count($values) !== count(self::HEADERS)) {
                $errors[] = "Row {$line}: expected ".count(self::HEADERS).' columns.';
                continue;
            }
            $row = array_combine(self::HEADERS, array_map(fn ($value) => trim((string) $value), $values));
            $key = mb_strtolower(preg_replace('/\s+/', ' ', $row['title']));
            if (isset($seen[$key]) || Notice::withTrashed()->whereRaw('LOWER(title) = ?', [mb_strtolower($row['title'])])->exists()) {
                $errors[] = "Row {$line}: this notice title already exists or is duplicated in the file.";
                continue;
            }
            $validator = Validator::make($row, $this->rules());
            if ($validator->fails()) {
                $errors[] = "Row {$line}: ".$validator->errors()->first();
                continue;
            }
            $validated = $validator->validated();
            if ($validated['publish_at'] && $validated['expires_at'] && strtotime($validated['expires_at']) <= strtotime($validated['publish_at'])) {
                $errors[] = "Row {$line}: expiration must be later than the publish date.";
                continue;
            }
            $seen[$key] = true;
            $rows[] = $this->map($validated);
        }

        if ($errors !== []) throw ValidationException::withMessages(['notice_file' => $errors]);
        if ($rows === []) throw ValidationException::withMessages(['notice_file' => 'The file contains no notice rows.']);

        DB::transaction(fn () => collect($rows)->each(fn ($row) => Notice::create($row)));
        return redirect()->route('allNotice')->with('success', count($rows).' notices imported as prepared.');
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, self::HEADERS);
            fputcsv($stream, ['Trial exam schedule', 'Review the official schedule and requirements.', 'ट्रायल परीक्षा तालिका', 'आधिकारिक तालिका र आवश्यकताहरू हेर्नुहोस्।', 'https://example.com/notice', 'Draft', '2026-09-15 09:00', '2026-09-30 23:59']);
            fclose($stream);
        }, 'notice-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');
        $rows = Notice::orderBy('id')->cursor()->map(fn ($notice) => [$notice->title, $notice->description, $notice->nepaliTitle, $notice->nepaliDescription, $notice->link, $notice->status, $notice->publish_at?->format('Y-m-d H:i'), $notice->expires_at?->format('Y-m-d H:i')]);
        return SpreadsheetDownload::make('notices-'.now()->format('Y-m-d'), self::HEADERS, $rows, $format);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'nepali_title' => ['required', 'string', 'max:255'],
            'nepali_description' => ['required', 'string', 'max:5000'],
            'link' => ['nullable', 'url', 'max:2048'],
            'status' => ['required', 'in:Draft,Published,Archived'],
            'publish_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    private function map(array $row): array
    {
        return ['title' => $row['title'], 'description' => $row['description'], 'nepaliTitle' => $row['nepali_title'], 'nepaliDescription' => $row['nepali_description'], 'link' => $row['link'] ?: null, 'status' => $row['status'], 'publish_at' => $row['publish_at'] ?: null, 'expires_at' => $row['expires_at'] ?: null];
    }
}
