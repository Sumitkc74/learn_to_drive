<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class ContentSourceHistory {
    /**
     * Latest run per source, keyed by source — the same MAX(id) query the
     * Import History page uses, so the notices/learning pages can show
     * when each source was last checked instead of when its rows were
     * first created. Pass source keys to limit the result to those.
     */
    public function latestRuns(array $sources = []): \Illuminate\Support\Collection {
        $query = DB::table('content_source_runs')->whereIn('id', DB::table('content_source_runs')->selectRaw('MAX(id)')->groupBy('source'));
        if ($sources !== []) $query->whereIn('source', $sources);
        return $query->get()->keyBy('source');
    }

    public function run(string $source, callable $fetch): array {
        $id=DB::table('content_source_runs')->insertGetId(['source'=>$source,'status'=>'Running','started_at'=>now()]);
        try {
            $result=$fetch();
            DB::table('content_source_runs')->where('id',$id)->update(['status'=>'Success','added'=>$result['added']??$result['created']??0,'known'=>$result['known']??$result['duplicates']??0,'finished_at'=>now()]);
            return $result;
        } catch (\Throwable $e) {
            // Record the real fetch error so a failing daily check is
            // diagnosable from Import History. Fetch errors name a host
            // or an HTTP status only; they contain no credentials.
            $message = $e->getMessage() !== '' ? $e->getMessage() : get_class($e);
            DB::table('content_source_runs')->where('id',$id)->update(['status'=>'Failed','error'=>$message,'finished_at'=>now()]);
            throw $e;
        }
    }
}
