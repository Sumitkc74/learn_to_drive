<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportSummary
{
    public static function finish(string $field, string $model, array $rows, array $errors, int $duplicates, int $blankRows): void
    {
        $summary = ['added' => 0, 'skipped' => $blankRows, 'duplicate' => $duplicates, 'invalid' => count($errors)];
        if ($errors !== []) {
            $summary['skipped'] += count($rows);
            session()->flash('import_summary', $summary);
            throw ValidationException::withMessages([$field => $errors]);
        }
        if ($rows === [] && $duplicates === 0) {
            session()->flash('import_summary', $summary);
            throw ValidationException::withMessages([$field => 'The file contains no data rows.']);
        }
        DB::transaction(fn () => collect($rows)->each(fn ($row) => $model::create($row)));
        $summary['added'] = count($rows);
        session()->flash('import_summary', $summary);
    }
}
