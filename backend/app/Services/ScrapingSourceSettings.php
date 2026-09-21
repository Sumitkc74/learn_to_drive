<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class ScrapingSourceSettings {
    public function all(): array {
        $rows=DB::table('scraping_sources')->get()->keyBy('key'); $result=[];
        $names=['government-notices'=>'Government notices'];
        foreach(config('official-content.sources') as $key=>$source) $names[$key]=$source['name'];
        foreach($names as $key=>$name) $result[$key]=['name'=>$name,'enabled'=>(bool)($rows[$key]->enabled??true),'daily_time'=>$rows[$key]->daily_time??($key==='government-notices'?'02:00':'02:10')];
        return $result;
    }
    public function enabled(string $key): bool { return $this->all()[$key]['enabled']??false; }
}
