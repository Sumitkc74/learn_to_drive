<?php
namespace App\Console\Commands;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class ProtectQuestionMedia extends \Illuminate\Console\Command {
    protected $signature='media:protect-questions';
    protected $description='Move question images from public storage into protected storage, preserving media IDs';
    public function handle(): int {
        $target=Storage::disk('protected-media'); $count=0;
        foreach (Media::where('model_type',(new \App\Models\Question)->getMorphClass())->where('disk','public')->cursor() as $media) {
            $source=Storage::disk('public'); $path=$media->getPathRelativeToRoot();
            if (!str_starts_with($path,$media->id.'/') || str_contains($path,'..')) { $this->error('Unexpected media path.'); return self::FAILURE; }
            if (!$source->exists($path)) { $this->error('Missing original for media #'.$media->id); return self::FAILURE; }
            $files=$source->allFiles($media->id.'/');
            foreach ($files as $file) {
                $bytes=$source->get($file);
                if ($target->exists($file) && hash('sha256',$target->get($file))!==hash('sha256',$bytes)) { $this->error('Destination conflict.'); return self::FAILURE; }
                $target->put($file,$bytes);
                if (hash('sha256',$bytes)!==hash('sha256',$target->get($file))) { $this->error('Copy verification failed.'); return self::FAILURE; }
            }
            $media->disk='protected-media';
            if ($media->conversions_disk==='public') $media->conversions_disk='protected-media';
            $media->saveQuietly();
            foreach ($files as $file) if (!$source->delete($file)) { $this->error('Could not remove public copy: '.$file); return self::FAILURE; }
            $count++;
        }
        $this->info("Protected {$count} question media records."); return self::SUCCESS;
    }
}
