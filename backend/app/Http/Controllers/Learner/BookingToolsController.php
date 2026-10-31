<?php
namespace App\Http\Controllers\Learner;
use App\Http\Controllers\Controller;
use App\Models\{DrivingLesson,LessonBooking,User};
use App\Services\{LessonWaitlist,SchoolNotifications};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class BookingToolsController extends Controller {
    public function waitlist(Request $r,int $lesson){
        $d=$r->validate(['action'=>'required|in:join,leave']);
        DB::transaction(function()use($r,$lesson,$d){
            $slot=DrivingLesson::whereKey($lesson)->lockForUpdate()->firstOrFail();
            if($d['action']==='leave') {DB::table('lesson_waitlist')->where('driving_lesson_id',$lesson)->where('user_id',$r->user()->id)->delete();return;}
            abort_unless(DrivingLesson::availableProvider()->whereKey($lesson)->exists() && !$slot->cancelled && $slot->starts_at->isFuture(),422);
            abort_if($slot->vehicle->instructor_id===$r->user()->id,403);
            abort_unless($slot->bookings()->where('status','Booked')->exists(),422);
            abort_if($slot->bookings()->where('status','Booked')->where('learner_id',$r->user()->id)->exists(),422);
            DB::table('lesson_waitlist')->updateOrInsert(['driving_lesson_id'=>$lesson,'user_id'=>$r->user()->id],['status'=>'Waiting','created_at'=>now(),'updated_at'=>now()]);
        });
        return back()->with('success',__('Waitlist updated.'));
    }
    public function form(Request $r,int $booking){
        $booking=LessonBooking::where('learner_id',$r->user()->id)->with('lesson.vehicle')->findOrFail($booking);
        abort_unless($booking->status==='Booked' && $booking->lesson->starts_at->isFuture(),422);
        $lessons=DrivingLesson::availableProvider()->where('cancelled',false)->where('starts_at','>',now())
            ->where('id','!=',$booking->driving_lesson_id)->where('price_npr',$booking->lesson->price_npr)
            ->whereHas('vehicle',fn($q)=>$q->where('driving_school_id',$booking->lesson->vehicle->driving_school_id))
            ->whereDoesntHave('bookings',fn($q)=>$q->where('status','Booked'))
            ->with('vehicle.school','vehicle.instructor')->orderBy('starts_at')->paginate(12);
        return view('learner.lessons.reschedule',compact('booking','lessons'));
    }
    public function reschedule(Request $r,int $booking){
        $d=$r->validate(['lesson_id'=>'required|integer']);
        DB::transaction(function()use($r,$booking,$d){
            User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $record=LessonBooking::where('learner_id',$r->user()->id)->findOrFail($booking);
            $slots=DrivingLesson::whereIn('id',[$record->driving_lesson_id,$d['lesson_id']])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $old=$slots->get($record->driving_lesson_id);$next=$slots->get($d['lesson_id']);
            $record=LessonBooking::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_unless($old && $next && $old->id!==$next->id && $record->driving_lesson_id===$old->id && $record->status==='Booked' && $old->starts_at->isFuture(),422);
            abort_unless(!$next->cancelled && $next->starts_at->isFuture() && DrivingLesson::availableProvider()->whereKey($next->id)->exists(),422);
            abort_if($next->vehicle->instructor_id===$r->user()->id,403);
            abort_unless($old->vehicle->driving_school_id===$next->vehicle->driving_school_id && $old->price_npr===$next->price_npr,422);
            if($next->bookings()->where('status','Booked')->exists()) throw ValidationException::withMessages(['lesson_id'=>__('This lesson is no longer available.')]);
            if(LessonBooking::where('learner_id',$r->user()->id)->where('status','Booked')->where('id','!=',$record->id)->whereHas('lesson',fn($q)=>$q->overlapping($next->starts_at,$next->ends_at))->exists()
                || DB::table('school_class_sessions as s')->join('school_batch_members as m','m.school_batch_id','=','s.school_batch_id')->where('m.user_id',$r->user()->id)->where('s.cancelled',false)->where('s.starts_at','<',$next->ends_at)->where('s.ends_at','>',$next->starts_at)->exists())
                throw ValidationException::withMessages(['lesson_id'=>__('You already have a booking at this time.')]);
            $payment=$record->payment()->lockForUpdate()->first();
            abort_if($payment && !in_array($payment->status,['Pending','Paid'],true),422);
            $record->update(['driving_lesson_id'=>$next->id,'active_lesson_id'=>$next->id]);
            DB::table('lesson_booking_changes')->insert(['lesson_booking_id'=>$record->id,'from_lesson_id'=>$old->id,'to_lesson_id'=>$next->id,'created_at'=>now(),'updated_at'=>now()]);
            DB::table('lesson_waitlist')->where('driving_lesson_id',$next->id)->where('user_id',$r->user()->id)->delete();
            LessonWaitlist::released($old->id);
            foreach(collect([$r->user()->id,$old->vehicle->instructor_id,$next->vehicle->instructor_id])->unique() as $id)
                SchoolNotifications::send($id,'A driving lesson was rescheduled. Check the updated booking time.');
        });
        return redirect()->route('learn.lessons.bookings')->with('success',__('Booking rescheduled. Your existing payment stays with this booking.'));
    }
}
