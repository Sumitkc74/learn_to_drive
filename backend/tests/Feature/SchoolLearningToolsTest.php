<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Storage};
use App\Models\{User,DrivingSchool,LessonVehicle,DrivingLesson,LessonBooking,SchoolLessonPayment};
class SchoolLearningToolsTest extends TestCase {
    use RefreshDatabase;
    private function setupSchool():array {
        $school=DrivingSchool::create(['name'=>'Tools School','status'=>'Approved']);$users=[];
        foreach(['Owner','Instructor','Student','Student'] as $role){$u=User::factory()->create(['is_active'=>true]);$school->memberships()->create(['user_id'=>$u->id,'role'=>$role]);$users[]=$u;}
        [$owner,$teacher,$student,$other]=$users;
        $car=LessonVehicle::create(['driving_school_id'=>$school->id,'instructor_id'=>$teacher->id,'name'=>'Car','registration'=>'TOOLS-1','transmission'=>'Manual']);
        $slots=[];foreach([1,2,3] as $i)$slots[]=DrivingLesson::create(['lesson_vehicle_id'=>$car->id,'starts_at'=>now()->addDays($i),'ends_at'=>now()->addDays($i)->addHour(),'price_npr'=>1000,'meeting_point'=>'School']);
        return [$school,$owner,$teacher,$student,$other,$slots];
    }
    public function test_reschedule_preserves_payment_and_notifies_waitlist_once():void {
        [$school,$owner,$teacher,$student,$other,$slots]=$this->setupSchool();
        $this->actingAs($student)->post(route('learn.lessons.book',$slots[0]->id))->assertRedirect();$b=LessonBooking::first();
        $p=new SchoolLessonPayment;$p->forceFill(['id'=>(string)\Illuminate\Support\Str::uuid(),'lesson_booking_id'=>$b->id,'amount_paisa'=>100000,'gateway'=>'demo','live'=>false,'status'=>'Paid'])->save();
        $this->actingAs($other)->post(route('learn.lessons.waitlist',$slots[0]->id),['action'=>'join'])->assertSessionHasNoErrors();
        $this->get(route('learn.lessons',['availability'=>'booked']))->assertOk()->assertSee('Leave waitlist');
        $this->post(route('learn.lessons.reschedule.save',$b->id),['lesson_id'=>$slots[1]->id])->assertNotFound();
        $this->actingAs($student)->get(route('learn.lessons.reschedule',$b->id))->assertOk()->assertSee('Confirm new time');
        $this->post(route('learn.lessons.reschedule.save',$b->id),['lesson_id'=>$slots[1]->id])->assertSessionHasNoErrors();
        $this->assertSame($slots[1]->id,$b->fresh()->driving_lesson_id);$this->assertSame('Paid',$p->fresh()->status);
        $this->assertDatabaseHas('lesson_booking_changes',['lesson_booking_id'=>$b->id,'from_lesson_id'=>$slots[0]->id,'to_lesson_id'=>$slots[1]->id]);
        $this->assertDatabaseHas('lesson_waitlist',['user_id'=>$other->id,'status'=>'Notified']);
        $this->assertDatabaseHas('school_notifications',['user_id'=>$other->id,'message'=>'A lesson on your waitlist is available. Open private lessons to book it.']);
        $this->actingAs($other)->post(route('learn.lessons.book',$slots[0]->id))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('lesson_waitlist',['user_id'=>$other->id]);
    }
    public function test_reschedule_rejects_price_changes_and_occupied_times_without_losing_original():void {
        [$school,$owner,$teacher,$student,$other,$slots]=$this->setupSchool();
        $this->actingAs($student)->post(route('learn.lessons.book',$slots[0]->id));$b=LessonBooking::first();
        $slots[1]->update(['price_npr'=>2000]);
        $this->post(route('learn.lessons.reschedule.save',$b->id),['lesson_id'=>$slots[1]->id])->assertUnprocessable();
        $this->actingAs($other)->post(route('learn.lessons.book',$slots[2]->id));
        $this->actingAs($student)->post(route('learn.lessons.reschedule.save',$b->id),['lesson_id'=>$slots[2]->id])->assertSessionHasErrors('lesson_id');
        $this->assertSame($slots[0]->id,$b->fresh()->active_lesson_id);
        $this->post(route('learn.lessons.waitlist',$slots[1]->id),['action'=>'join'])->assertUnprocessable();
    }
    public function test_feedback_readiness_and_reports_are_scoped():void {
        [$school,$owner,$teacher,$student,$other]=$this->setupSchool();
        $course=DB::table('school_courses')->insertGetId(['driving_school_id'=>$school->id,'title'=>'Basics','description'=>'Lessons']);
        $batch=DB::table('school_batches')->insertGetId(['school_course_id'=>$course,'instructor_id'=>$teacher->id,'name'=>'Batch','starts_on'=>now()->subWeek(),'ends_on'=>now()->addWeek(),'capacity'=>10]);
        DB::table('school_batch_members')->insert(['school_batch_id'=>$batch,'user_id'=>$student->id]);
        $session=DB::table('school_class_sessions')->insertGetId(['school_batch_id'=>$batch,'starts_at'=>now()->subHours(2),'ends_at'=>now()->subHour(),'location'=>'School']);
        $base='/api/school-centre/'.$school->id.'/students/'.$student->id;
        $payload=['session_id'=>$session,'skill'=>'Parking','assessment'=>'Developing','notes'=>'Practise slow control next.'];
        $this->actingAs($teacher)->postJson($base.'/feedback',$payload)->assertUnprocessable();
        DB::table('school_attendance')->insert(['school_class_session_id'=>$session,'user_id'=>$student->id,'status'=>'Present','marked_by'=>$teacher->id]);
        $this->postJson($base.'/feedback',$payload)->assertOk();
        $this->postJson($base.'/feedback',array_replace($payload,['assessment'=>'Confident']))->assertOk();
        $this->assertDatabaseCount('school_lesson_feedback',1);
        $this->getJson($base.'/readiness')->assertJsonPath('data.practice_count',null)->assertJsonPath('data.feedback.data.0.assessment','Confident');
        $this->actingAs($student)->getJson($base.'/readiness')->assertOk()->assertJsonPath('data.canEdit',false);
        $this->postJson($base.'/feedback',$payload)->assertForbidden();
        $this->actingAs($other)->getJson($base.'/readiness')->assertNotFound();
        $this->actingAs($owner)->get(route('learn.school.readiness',[$school->id,$student->id]))->assertOk()->assertSee('Parking');
        $this->get(route('learn.school.report',$school->id))->assertOk()->assertSee('Export CSV');
        $this->get(route('learn.school.report',[$school->id,'format'=>'csv']))->assertOk()->assertDownload('school-report.csv');
        $this->actingAs($teacher)->getJson('/api/school-centre/'.$school->id.'/report')->assertForbidden();
    }
    public function test_reminders_are_deduplicated_and_stop_for_suspended_schools():void {
        [$school,$owner,$teacher,$student]=$this->setupSchool();
        DB::table('school_student_exams')->insert(['driving_school_id'=>$school->id,'user_id'=>$student->id,'category'=>'B','exam_type'=>'Written','exam_date'=>now('Asia/Kathmandu')->addDay()->toDateString(),'result'=>'Scheduled']);
        DB::table('school_student_exams')->insert(['driving_school_id'=>$school->id,'user_id'=>$student->id,'category'=>'A','exam_type'=>'Written','exam_date'=>now('Asia/Kathmandu')->subDay()->toDateString(),'result'=>'Awaiting result']);
        $this->artisan('schools:send-reminders')->assertSuccessful();$count=DB::table('school_notifications')->count();$this->assertSame(2,$count);
        $this->artisan('schools:send-reminders')->assertSuccessful();$this->assertSame($count,DB::table('school_notifications')->count());
        $school->update(['status'=>'Suspended']);DB::table('school_reminder_deliveries')->delete();
        $this->artisan('schools:send-reminders')->assertSuccessful();$this->assertSame($count,DB::table('school_notifications')->count());
    }
    public function test_demo_reset_is_dry_by_default_and_refuses_real_members():void {
        Storage::fake('public');Storage::fake('protected-media');$this->seed(\Database\Seeders\DemoPreviewSeeder::class);
        $file=tempnam(sys_get_temp_dir(),'demo-baseline-');unlink($file);
        try{
            $state=new \App\Services\DemoPreviewState($file);
            $accounts=User::where('email','like','demo.%@example.test')->get()->all();$state->capture($accounts);
            $school=DrivingSchool::where('name','[DEMO] Kathmandu Driving Academy')->first();$original=$school->address;$school->update(['address'=>'Edited preview']);
            $state->restore(false);$this->assertSame('Edited preview',$school->fresh()->address);
            $state->restore(true);$this->assertSame($original,$school->fresh()->address);
            $real=User::factory()->create();$school->memberships()->create(['user_id'=>$real->id,'role'=>'Student']);
            $this->expectException(\RuntimeException::class);$state->restore(true);
        }finally{if(is_file($file))unlink($file);}
    }
}
