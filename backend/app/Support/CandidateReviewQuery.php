<?php

namespace App\Support;

use App\Models\PdfQuestionImport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CandidateReviewQuery
{
    public static function filters(Request $request): array
    {
        return validator($request->query(), [
            'status' => ['nullable', Rule::in(['Pending', 'Imported', 'Rejected'])],
            'needs' => ['nullable', Rule::in(['answer', 'diagram', 'options', 'ocr'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ])->validate();
    }

    public static function query(int $resourceId, array $filters)
    {
        $query = PdfQuestionImport::where('learning_content_import_id', $resourceId)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
        match ($filters['needs'] ?? '') {
            'answer' => $query->where('needs_answer', true),
            'diagram' => $query->where('needs_diagram', true),
            'options' => $query->where('incomplete_options', true),
            'ocr' => $query->where('is_ocr', true),
            default => null,
        };

        return $query;
    }

    public static function neighbor(PdfQuestionImport $candidate, array $filters, bool $next = true): ?PdfQuestionImport
    {
        $operator = $next ? '>' : '<';
        $direction = $next ? 'asc' : 'desc';

        return self::query($candidate->learning_content_import_id, $filters)->where(function ($query) use ($candidate, $operator) {
            $query->where('page', $operator, $candidate->page)
                ->orWhere(fn ($q) => $q->where('page', $candidate->page)->where('number', $operator, $candidate->number))
                ->orWhere(fn ($q) => $q->where('page', $candidate->page)->where('number', $candidate->number)->where('id', $operator, $candidate->id));
        })->orderBy('page', $direction)->orderBy('number', $direction)->orderBy('id', $direction)->first();
    }
}
