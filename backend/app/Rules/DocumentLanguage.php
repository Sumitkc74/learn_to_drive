<?php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DocumentLanguage implements ValidationRule
{
    public function __construct(private readonly mixed $language) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !in_array($this->language, ['English', 'Nepali'], true)) return;
        // A script check, not automatic translation or PDF language detection.
        $letters = preg_replace('/[^\p{L}]/u', '', $value);
        $nepali = preg_match_all('/\p{Devanagari}/u', $letters);
        $latin = preg_match_all('/\p{Latin}/u', $letters);
        $matches = $this->language === 'Nepali'
            ? $nepali > 0 && $nepali > mb_strlen($letters) / 2
            : $latin > 0 && $latin === mb_strlen($letters);
        if (!$matches) {
            $label = $attribute === 'name' ? 'title' : 'description';
            $fail("Write the {$label} in {$this->language} to match the selected PDF language.");
        }
    }
}
