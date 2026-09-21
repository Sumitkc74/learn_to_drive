<?php
namespace App\Observers;
use App\Support\VersionedContent;
use Illuminate\Support\Facades\DB;
class ContentVersionObserver {
    private function write($record,array $attributes): void {
        DB::table('content_versions')->insert(['content_type'=>VersionedContent::type($record),'content_id'=>$record->id,'actor_id'=>auth()->id(),
            'snapshot'=>json_encode(array_intersect_key($attributes,array_flip(VersionedContent::fields($record)))),'created_at'=>now()]);
    }
    public function created($record): void { $this->write($record,$record->getAttributes()); }
    public function updating($record): void {
        if (!array_intersect(array_keys($record->getDirty()),VersionedContent::fields($record))) return;
        if (!DB::table('content_versions')->where('content_type',VersionedContent::type($record))->where('content_id',$record->id)->exists()) $this->write($record,$record->getRawOriginal());
    }
    public function updated($record): void {
        if(array_intersect(array_keys($record->getChanges()),VersionedContent::fields($record))) $this->write($record,$record->getAttributes());
    }
}
