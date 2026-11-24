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

    /**
     * AI screening counts over one set of records, so the Learning
     * Content page can never disagree with the queue it lists: the
     * caller passes 'Pending', so the counts cover the review queue
     * only (approved/rejected rows are excluded, even though their
     * ai_status stays as last screened). Pass null only for an ad-hoc
     * count over all rows. Mirrors GovernmentNoticeImport::screeningCounts.
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
}
