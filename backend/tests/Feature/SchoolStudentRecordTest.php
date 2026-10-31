<?php
namespace Tests\Feature;

use App\Models\{DrivingSchool,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolStudentRecordTest extends TestCase {
    use RefreshDatabase;
    private function school():array {
        $school=DrivingSchool::create(['name'=>'Exam Academy','status'=>'Approved']);
        $users=[];
        foreach(['Owner','Student','Instructor','Student'] as $role) {
            $user=User::factory()->create(['is_active'=>true]);
            $school->memberships()->create(['user_id'=>$user->id,'role'=>$role]);$users[]=$user;
        }
        return [$school,...$users];
    }
    private function exam(array $extra=[]):array {
        return array_replace(['category'=>'b','exam_type'=>'Written','exam_date'=>now('Asia/Kathmandu')->addDay()->toDateString(),'result'=>'Scheduled','centre'=>'Test office','notes'=>'Bring your booking confirmation.'],$extra);
    }
    private function url($school,$student):string {return '/api/school-centre/'.$school->id.'/students/'.$student->id;}

    public function test_managers_record_retakes_and_previous_categories_and_student_can_read():void {
        [$school,$owner,$student]=$this->school();$url=$this->url($school,$student);
        $this->actingAs($owner)->postJson($url.'/exam',$this->exam())->assertOk();
        $this->assertDatabaseHas('school_student_exams',['category'=>'B','user_id'=>$student->id,'result'=>'Scheduled']);
        $this->postJson($url.'/exam',$this->exam())->assertUnprocessable();
        $exam=DB::table('school_student_exams')->first();
        $this->postJson($url.'/exam',$this->exam(['id'=>$exam->id,'version'=>1,'exam_date'=>now()->subDays(3)->toDateString(),'result'=>'Failed','result_date'=>now()->subDays(2)->toDateString()]))->assertOk();
        $this->postJson($url.'/exam',$this->exam())->assertOk();
        $this->postJson($url.'/licence',['category'=>'a','issued_on'=>'2020-01-01','expires_on'=>'2030-01-01','notes'=>'Previously held category'])->assertOk();
        $this->assertDatabaseHas('school_student_licences',['category'=>'A']);
        $this->get(route('learn.school.students.show',[$school->id,$student->id]))->assertOk()->assertSee('Edit exam record')->assertSee('Previous licences');
        $this->actingAs($student)->getJson($url)->assertOk()->assertJsonCount(2,'data.exams.data')->assertJsonCount(1,'data.licences')->assertJsonPath('data.canEdit',false);
        $this->get(route('learn.school.students.show',[$school->id,$student->id]))->assertOk()->assertDontSee('Edit exam record');
        $this->assertDatabaseHas('audit_logs',['event'=>'school.student_exam_saved']);
    }
    public function test_other_students_foreign_staff_and_unassigned_instructors_cannot_read_or_write():void {
        [$school,$owner,$student,$teacher,$otherStudent]=$this->school();
        [$foreign,$foreignOwner]=$this->school();$url=$this->url($school,$student);
        $this->actingAs($owner)->postJson($url.'/exam',$this->exam())->assertOk();
        foreach([$otherStudent,$teacher,$foreignOwner] as $other) $this->actingAs($other)->getJson($url)->assertNotFound();
        foreach([$student,$teacher] as $other) $this->actingAs($other)->postJson($url.'/exam',$this->exam())->assertForbidden();
        $this->actingAs($foreignOwner)->postJson($url.'/exam',$this->exam())->assertNotFound();
        $this->actingAs($student)->getJson('/api/school-centre/'.$school->id.'/students')->assertJsonCount(1,'data.data');
        $this->actingAs($owner)->get(route('learn.school.students',$school->id))->assertOk()->assertSee($student->name);
        $school->update(['status'=>'Suspended']);$this->getJson($url)->assertNotFound();
    }
    public function test_assigned_instructor_can_read_but_cannot_edit():void {
        [$school,$owner,$student,$teacher]=$this->school();
        $course=DB::table('school_courses')->insertGetId(['driving_school_id'=>$school->id,'title'=>'Course','description'=>'Basics']);
        $batch=DB::table('school_batches')->insertGetId(['school_course_id'=>$course,'instructor_id'=>$teacher->id,'name'=>'Batch','starts_on'=>'2026-01-01','ends_on'=>'2026-12-31','capacity'=>10]);
        DB::table('school_batch_members')->insert(['school_batch_id'=>$batch,'user_id'=>$student->id]);
        $url=$this->url($school,$student);
        $this->actingAs($teacher)->getJson($url)->assertOk()->assertJsonPath('data.canEdit',false);
        $this->postJson($url.'/licence',['category'=>'B'])->assertForbidden();
    }
    public function test_schedule_filters_respect_student_visibility():void {
        [$school,$owner,$student,$teacher,$other]=$this->school();
        $this->actingAs($owner)->postJson($this->url($school,$student).'/exam',$this->exam())->assertOk();
        $this->postJson($this->url($school,$other).'/exam',$this->exam(['exam_date'=>now()->subDays(3)->toDateString(),'result'=>'Passed','result_date'=>now()->subDay()->toDateString()]))->assertOk();
        $base='/api/school-centre/'.$school->id.'/students';
        $this->getJson($base.'?result=Passed')->assertOk()->assertJsonCount(1,'exam_schedule.data')->assertJsonPath('exam_schedule.data.0.user_id',$other->id);
        $this->getJson($base.'?date_from='.now('Asia/Kathmandu')->toDateString())->assertJsonCount(1,'exam_schedule.data')->assertJsonPath('exam_schedule.data.0.user_id',$student->id);
        $this->actingAs($student)->getJson($base)->assertJsonCount(1,'data.data')->assertJsonCount(1,'exam_schedule.data')->assertDontSee($other->email);
        $this->getJson($this->url($school,$other))->assertNotFound();
    }
    public function test_invalid_dates_stale_updates_and_foreign_record_ids_are_rejected():void {
        [$school,$owner,$student,$teacher,$other]=$this->school();$url=$this->url($school,$student);
        $this->actingAs($owner)->postJson($url.'/exam',$this->exam(['result'=>'Passed','result_date'=>now()->toDateString()]))->assertUnprocessable();
        $this->postJson($url.'/exam',$this->exam(['category'=>['B']]))->assertUnprocessable();
        $this->postJson($url.'/licence',['category'=>'A','issued_on'=>'2025-01-01','expires_on'=>'2024-01-01'])->assertUnprocessable();
        $this->postJson($url.'/exam',$this->exam())->assertOk();$exam=DB::table('school_student_exams')->first();
        $this->postJson($url.'/exam',$this->exam(['id'=>$exam->id,'version'=>1,'centre'=>'Updated centre']))->assertOk();
        $this->postJson($url.'/exam',$this->exam(['id'=>$exam->id,'version'=>1,'centre'=>'Stale change']))->assertUnprocessable();
        $this->postJson($this->url($school,$other).'/exam',$this->exam(['id'=>$exam->id,'version'=>2]))->assertNotFound();
        $this->assertDatabaseHas('school_student_exams',['id'=>$exam->id,'centre'=>'Updated centre','version'=>2]);
        $school->memberships()->where('user_id',$student->id)->delete();
        $this->getJson($url)->assertNotFound();
        $this->assertDatabaseCount('school_student_exams',1);
    }
}
