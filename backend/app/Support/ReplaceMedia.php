<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;

class ReplaceMedia
{
    public static function at(HasMedia $record, UploadedFile $file, int $position = 0): void
    {
        $previous = $record->getMedia()->get($position);
        $replacement = $record->addMedia($file)->toMediaCollection();
        $replacement->order_column = $previous?->order_column ?? $position + 1;
        $replacement->save();
        $previous?->delete();
        $record->unsetRelation('media');
    }
}
