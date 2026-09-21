<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class MediaStorageAudit {
    public function scan(): array {
        $issues=[]; $roots=[]; $references=[];
        foreach (Media::cursor() as $media) {
            $path=$media->getPathRelativeToRoot();
            $roots[$media->disk][explode('/',$path)[0]]=true;
            $roots[$media->conversions_disk ?: $media->disk][explode('/',$path)[0]]=true;
            try {
                if (!Storage::disk($media->disk)->exists($path)) $issues[]=['type'=>'Missing file','disk'=>$media->disk,'path'=>$path,'record'=>'Media #'.$media->id];
                $owner=$media->model;
                if (!$owner) {
                    $class=\Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($media->model_type) ?? $media->model_type;
                    if (class_exists($class) && in_array(\Illuminate\Database\Eloquent\SoftDeletes::class,class_uses_recursive($class))) $owner=$class::withTrashed()->find($media->model_id);
                }
                if (!$owner) $issues[]=['type'=>'Missing owner','disk'=>$media->disk,'path'=>$path,'record'=>'Media #'.$media->id];
            } catch (\Throwable $e) { $issues[]=['type'=>'Storage or owner unavailable','disk'=>$media->disk,'path'=>$path,'record'=>'Media #'.$media->id]; }
        }
        foreach (\App\Models\LearningContentImport::whereNotNull('file_path')->cursor() as $record) {
            $references[$record->file_path]=true;
            try { if (!Storage::disk('learning-content')->exists($record->file_path)) $issues[]=['type'=>'Missing file','disk'=>'learning-content','path'=>$record->file_path,'record'=>'Learning resource #'.$record->id]; }
            catch (\Throwable $e) { $issues[]=['type'=>'Storage unavailable','disk'=>'learning-content','path'=>$record->file_path,'record'=>'Learning resource #'.$record->id]; }
        }
        foreach (['public','protected-media','learning-content'] as $name) {
            try {
                foreach (Storage::disk($name)->allFiles() as $path) {
                    if (basename($path)==='.gitignore') continue;
                    if ($name==='learning-content' && (str_starts_with($path,'pdf-previews/') || str_starts_with($path,'translation-builds/'))) continue;
                    $known=$name==='learning-content' ? isset($references[$path]) : isset($roots[$name][explode('/',$path)[0]]);
                    if (!$known) $issues[]=['type'=>'Possibly unused upload','disk'=>$name,'path'=>$path,'record'=>'No matching database reference'];
                }
            } catch (\Throwable $e) { $issues[]=['type'=>'Storage unavailable','disk'=>$name,'path'=>'','record'=>'File listing failed']; }
        }
        return ['checked_at'=>now()->toIso8601String(),'issues'=>$issues];
    }
}
