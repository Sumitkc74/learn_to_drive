<?php
namespace App\Http\Controllers\Admin;
class SourceRevisionController extends \App\Http\Controllers\Controller {
    public function check(\App\Models\LearningContentImport $resource) {
        abort_unless($resource->status==='Approved' && !$resource->parent_resource_id && $resource->file_hash,409);
        abort_unless(isset(config('official-content.sources')[$resource->source_key]) && app(\App\Services\ScrapingSourceSettings::class)->enabled($resource->source_key),422,'Only enabled official sources can be rechecked.');
        \App\Jobs\CheckSourceRevision::dispatch($resource->id);
        return back()->with('success','Source change check queued. Existing files and published content remain unchanged.');
    }
}
