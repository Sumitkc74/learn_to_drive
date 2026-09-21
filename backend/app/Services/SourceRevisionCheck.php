<?php
namespace App\Services;
use App\Models\LearningContentImport;
use Illuminate\Support\Facades\{DB,Storage};
class SourceRevisionCheck {
    public function check(LearningContentImport $resource): void {
        if($resource->status!=='Approved' || $resource->parent_resource_id || !$resource->file_hash) return;
        if(!isset(config('official-content.sources')[$resource->source_key]) || !app(ScrapingSourceSettings::class)->enabled($resource->source_key)) return;
        $file=null;$retained=false;
        try {
            $file=app(LearningContentDownload::class)->fetch($resource);
            DB::transaction(function() use($resource,$file,&$retained){
                $original=LearningContentImport::lockForUpdate()->findOrFail($resource->id);
                if($original->status!=='Approved') return;
                $latest=LearningContentImport::where('parent_resource_id',$original->id)->where('status','Approved')->latest('id')->first();
                $referenceHash=$latest?->file_hash ?? $original->file_hash;
                $state='Unchanged';
                if(!hash_equals($referenceHash,$file['file_hash'])) {
                    $key=hash('sha256',$original->asset_url.'|revision|'.$file['file_hash']);
                    $revision=LearningContentImport::where('url_hash',$key)->first();
                    if(!$revision) {
                        $revision=new LearningContentImport($original->only(['source_key','source_name','source_url','asset_url','kind','title']));
                        $revision->forceFill($file+['url_hash'=>$key,'parent_resource_id'=>$original->id,'fetched_at'=>now(),'status'=>'Pending']);
                        $revision->save();$retained=true;
                    }
                    $state=$revision->status==='Rejected' ? 'Change rejected' : 'Needs review';
                }
                $original->forceFill(['source_checked_at'=>now(),'source_check_status'=>$state])->save();
            });
        } catch(\Throwable $e) {
            $resource->forceFill(['source_checked_at'=>now(),'source_check_status'=>'Check failed'])->save();
        } finally {
            if($file && !$retained) Storage::disk('learning-content')->delete($file['file_path']);
        }
    }
}
