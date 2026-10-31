<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class LessonWaitlist {
    // Availability notifications are not reservations; the normal atomic booking claim still applies.
    public static function released(int $lesson):void {
        foreach(DB::table('lesson_waitlist')->where('driving_lesson_id',$lesson)->where('status','Waiting')->lockForUpdate()->get() as $entry){
            SchoolNotifications::send($entry->user_id,'A lesson on your waitlist is available. Open private lessons to book it.');
            DB::table('lesson_waitlist')->where('id',$entry->id)->update(['status'=>'Notified','updated_at'=>now()]);
        }
    }
}
