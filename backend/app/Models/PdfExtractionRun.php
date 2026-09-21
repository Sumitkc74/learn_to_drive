<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfExtractionRun extends Model
{
    protected $guarded = ['id'];

    protected $attributes = ['generation' => 1, 'status' => 'Queued', 'added' => 0, 'known' => 0, 'cancel_requested' => false];

    protected $casts = ['cancel_requested' => 'boolean', 'empty_pages' => 'array', 'generation' => 'integer', 'next_page' => 'integer', 'from_page' => 'integer', 'to_page' => 'integer'];

    public function resource()
    {
        return $this->belongsTo(LearningContentImport::class, 'learning_content_import_id');
    }
}
