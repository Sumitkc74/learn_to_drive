<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernmentNoticeImport extends Model
{
    protected $fillable = ['source_name', 'source_url', 'source_domain', 'title', 'content_hash', 'status', 'fetched_at', 'reviewed_by', 'reviewed_at'];

    protected $casts = ['fetched_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function notice()
    {
        return $this->hasOne(Notice::class, 'government_notice_import_id');
    }
}
