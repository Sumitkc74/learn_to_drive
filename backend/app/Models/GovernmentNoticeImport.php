<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernmentNoticeImport extends Model
{
    protected $fillable = ['source_name', 'source_url', 'source_domain', 'title', 'content_hash', 'status', 'fetched_at', 'reviewed_by', 'reviewed_at'];

    protected $casts = ['assigned_to'=>'integer', 'claimed_at'=>'datetime', 'fetched_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * AI screening counts over one set of records, so the Government
     * Notices page and the Review Operations government tab can never
     * disagree: both callers pass 'Pending', so both render the counts
     * for the review queue only (approved/rejected rows are excluded,
     * even though their ai_status stays as last screened). Pass null
     * only for an ad-hoc count over all rows.
     */
    public static function screeningCounts(?string $status = null): array
    {
        $query = static::query();
        if ($status !== null) $query->where('status', $status);
        $failed = (clone $query)->where('ai_status', 'Failed')->count();
        $flagged = (clone $query)->where('ai_status', 'Flagged')->count();
        $ready = (clone $query)->where('ai_status', 'Ready')->count();
        $deferred = (clone $query)->where('ai_status', 'Deferred')->count();
        $inProgress = (clone $query)->whereIn('ai_status', ['Queued', 'Running'])->count();
        $total = $query->count();

        return ['failed' => $failed, 'flagged' => $flagged, 'ready' => $ready, 'deferred' => $deferred, 'in_progress' => $inProgress, 'not_screened' => $total - $failed - $flagged - $ready - $deferred - $inProgress, 'total' => $total];
    }

    public function notice()
    {
        return $this->hasOne(Notice::class, 'government_notice_import_id');
    }
}
