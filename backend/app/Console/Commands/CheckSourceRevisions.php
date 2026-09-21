<?php
namespace App\Console\Commands;
class CheckSourceRevisions extends \Illuminate\Console\Command {
    protected $signature='content:check-revisions';
    protected $description='Queue up to 20 approved official files for source-change checks';
    public function handle(): int {
        $keys=array_keys(array_filter(app(\App\Services\ScrapingSourceSettings::class)->all(),fn($source)=>$source['enabled']));
        $rows=\App\Models\LearningContentImport::where('status','Approved')->whereNull('parent_resource_id')->whereNotNull('file_hash')->whereIn('source_key',$keys)
            ->where(fn($q)=>$q->whereNull('source_checked_at')->orWhere('source_checked_at','<=',now()->subDay()))->orderBy('source_checked_at')->orderBy('id')->limit(20)->get();
        foreach($rows as $resource) \App\Jobs\CheckSourceRevision::dispatch($resource->id);
        $this->info($rows->count().' source revision checks queued.'); return self::SUCCESS;
    }
}
