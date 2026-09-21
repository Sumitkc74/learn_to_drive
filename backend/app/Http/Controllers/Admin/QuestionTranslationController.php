<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PdfTextTranslator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionTranslationController extends Controller
{
    public function translate(Request $request, PdfTextTranslator $translator)
    {
        $limits = ['question' => 500, 'option1' => 255, 'option2' => 255, 'option3' => 255, 'option4' => 255, 'explanation' => 2000];
        $data = $request->validate([
            'field' => ['required', Rule::in(array_keys($limits))],
            'text' => ['required', 'string', 'max:2000'],
            'source_language' => ['required', Rule::in(['en', 'ne'])],
        ]);
        if (mb_strlen($data['text']) > $limits[$data['field']]) {
            return response()->json(['message' => 'The source text exceeds the field limit.'], 422);
        }
        if (!$translator->ready()) {
            return response()->json(['message' => 'Configure a translation provider first.'], 422);
        }
        set_time_limit(110);
        try {
            $text = $translator->translate($data['text'], $data['source_language'], $data['source_language'] === 'en' ? 'ne' : 'en');
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Translation failed. Check the provider or its quota and retry. Your question has not been changed.'], 502);
        }

        // Return the complete result for editing; never truncate answer text.
        return response()->json(['field' => $data['field'], 'text' => $text, 'max_length' => $limits[$data['field']]])
            ->header('Cache-Control', 'private, no-store');
    }
}
