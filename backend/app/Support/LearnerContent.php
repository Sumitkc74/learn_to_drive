<?php

namespace App\Support;

class LearnerContent
{
    public static function text($item, string $field): string
    {
        $translated = match ($field) {
            'name' => 'nepaliSignName',
            'title' => 'nepaliTitle',
            'description' => 'nepaliDescription',
            default => null,
        };
        if (app()->getLocale() === 'ne' && $translated && filled($item->getAttribute($translated))) {
            return (string) $item->getAttribute($translated);
        }
        return (string) ($item->getAttribute($field) ?? '');
    }
}
