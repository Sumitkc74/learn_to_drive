<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class ContentSourceHistory {
    public function run(string $source, callable $fetch): array {
        $id=DB::table('content_source_runs')->insertGetId(['source'=>$source,'status'=>'Running','started_at'=>now()]);
        try {
            $result=$fetch();
            DB::table('content_source_runs')->where('id',$id)->update(['status'=>'Success','added'=>$result['added']??$result['created']??0,'known'=>$result['known']??$result['duplicates']??0,'finished_at'=>now()]);
            return $result;
        } catch (\Throwable $e) {
            DB::table('content_source_runs')->where('id',$id)->update(['status'=>'Failed','error'=>'Source check failed. Check connectivity, certificate settings, or source availability.','finished_at'=>now()]);
            throw $e;
        }
    }
}
