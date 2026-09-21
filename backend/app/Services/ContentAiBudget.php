<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class ContentAiBudget {
    public function day(): string { return now('Asia/Kathmandu')->toDateString(); }
    public function reserve(): bool {
        $day=$this->day();
        DB::table('content_ai_usage')->insertOrIgnore(['day'=>$day,'requests'=>0,'tokens'=>0]);
        return DB::table('content_ai_usage')->where('day',$day)->where('requests','<',max(0,(int)config('content-screening.daily_limit')))->increment('requests')===1;
    }
}
