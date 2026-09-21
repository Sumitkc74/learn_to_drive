<?php

namespace App\Models;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Model;

class LearningContentImport extends Model
{
    use TracksCreator;

    protected $fillable = ['source_key', 'source_name', 'source_url', 'asset_url', 'url_hash', 'kind', 'title', 'fetched_at'];

    protected $casts = ['assigned_to'=>'integer', 'claimed_at'=>'datetime', 'fetched_at' => 'datetime', 'reviewed_at' => 'datetime', 'downloaded_at' => 'datetime'];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
