<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\SchoolNotifications;
class SendSchoolReminders extends Command {
    protected $signature='schools:send-reminders';
    protected $description='Send deduplicated in-app reminders for exams, lessons and outstanding results';
    private int $sent=0;
    private function deliver(int $user,string $key,string $message):void {
        if(!DB::table('users')->where('id',$user)->where('is_active',true)->exists())return;
        DB::transaction(function()use($user,$key,$message){
            if(DB::table('school_reminder_deliveries')->insertOrIgnore(['user_id'=>$user,'reminder_key'=>$key,'created_at'=>now(),'updated_at'=>now()])){
                SchoolNotifications::send($user,$message);$this->sent++;
            }
        });
    }
    public function handle():int {
        $today=now('Asia/Kathmandu')->toDateString();$tomorrow=now('Asia/Kathmandu')->addDay()->toDateString();
        DB::table('school_student_exams as e')->join('driving_schools as s','s.id','=','e.driving_school_id')->join('school_memberships as m',fn($j)=>$j->on('m.driving_school_id','=','e.driving_school_id')->on('m.user_id','=','e.user_id'))
            ->where('s.status','Approved')->where('m.role','Student')->whereIn('e.result',['Scheduled','Awaiting result'])->where('e.exam_date','<=',$tomorrow)->select('e.*')->orderBy('e.id')->chunk(100,function($exams)use($today){
                foreach($exams as $exam){
                    $upcoming=$exam->exam_date>=$today;
                    $key='exam:'.$exam->id.':'.$exam->exam_date.':'.($upcoming?'due':'result');
                    if($upcoming)$this->deliver($exam->user_id,$key,'An exam is due today or tomorrow. Check your student exam records.');
                    else foreach(DB::table('school_memberships')->where('driving_school_id',$exam->driving_school_id)->whereIn('role',['Owner','Manager'])->pluck('user_id') as $id)
                        $this->deliver($id,$key,'An exam result is still pending. Open the school exam schedule to review it.');
                }
            });
        \App\Models\LessonBooking::where('status','Booked')->whereHas('lesson',fn($q)=>$q->availableProvider()->where('cancelled',false)->whereBetween('starts_at',[now(),now()->addDay()]))
            ->with('lesson.vehicle')->chunkById(100,function($bookings){foreach($bookings as $b)foreach([$b->learner_id,$b->lesson->vehicle->instructor_id] as $user)
                $this->deliver($user,'lesson:'.$b->id.':'.$b->driving_lesson_id.':'.$b->lesson->starts_at->timestamp,'A driving lesson starts within 24 hours. Check your lesson schedule.');});
        $this->info($this->sent.' in-app reminders sent.');return self::SUCCESS;
    }
}
