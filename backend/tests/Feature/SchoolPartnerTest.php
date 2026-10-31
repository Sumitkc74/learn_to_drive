<?php
namespace Tests\Feature;

use App\Models\{DrivingSchool,SchoolMembership,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolPartnerTest extends TestCase {
    use RefreshDatabase;
    private function school():array {
        $owner=User::factory()->create(['is_active'=>true]);
        $teacher=User::factory()->create(['is_active'=>true]);
        $student=User::factory()->create(['is_active'=>true]);
        $school=DrivingSchool::create(['name'=>'Partner Academy','status'=>'Approved']);
        foreach([[$owner,'Owner'],[$teacher,'Instructor'],[$student,'Student']] as [$user,$role])
            $school->memberships()->create(['user_id'=>$user->id,'role'=>$role]);
        return [$school,$owner,$teacher,$student];
    }
    private function action($user,$school,$action,$data) {
        return $this->actingAs($user)->postJson('/api/school-centre/'.$school->id.'/'.$action,$data);
    }
    public function test_application_stays_pending_and_cannot_be_repeated():void {
        $user=User::factory()->create(['is_active'=>true]);
        $payload=['name'=>'New school','address'=>'Kathmandu','phone'=>'12345','email'=>'school@example.com','status'=>'Approved'];
        $this->actingAs($user)->postJson('/api/school-centre/apply',$payload)->assertOk();
        $this->assertDatabaseHas('driving_schools',['name'=>'New school','status'=>'Pending']);
        $this->postJson('/api/school-centre/apply',$payload)->assertUnprocessable();
        $this->get(route('learn.school.centre'))->assertOk()->assertSee('New school');
        $this->getJson('/api/school-centre/'.DrivingSchool::first()->id)->assertNotFound();
    }
    public function test_invitation_is_recipient_bound_and_expires():void {
        [$school,$owner,$teacher,$student]=$this->school();
        $new=User::factory()->create(['is_active'=>true]);
        $this->action($owner,$school,'invite',['email'=>$new->email,'role'=>'Manager'])->assertOk();
        $id=DB::table('school_invitations')->value('id');
        $this->assertDatabaseMissing('school_memberships',['user_id'=>$new->id]);
        $this->actingAs($student)->postJson('/api/school-centre/invitations/'.$id,['decision'=>'accept'])->assertNotFound();
        $this->actingAs($new)->postJson('/api/school-centre/invitations/'.$id,['decision'=>'accept'])->assertOk();
        $this->assertDatabaseHas('school_memberships',['user_id'=>$new->id,'role'=>'Manager']);
        $this->postJson('/api/school-centre/invitations/'.$id,['decision'=>'accept'])->assertNotFound();
        DB::table('school_invitations')->where('id',$id)->update(['status'=>'Pending','expires_at'=>now()->subDay()]);
        $this->postJson('/api/school-centre/invitations/'.$id,['decision'=>'accept'])->assertNotFound();
        $this->action($new,$school,'transfer',['user_id'=>$teacher->id])->assertForbidden();
        $this->action($owner,$school,'transfer',['user_id'=>$new->id])->assertOk();
        $this->assertDatabaseHas('school_memberships',['user_id'=>$owner->id,'role'=>'Manager']);
        $this->assertDatabaseHas('school_memberships',['user_id'=>$new->id,'role'=>'Owner']);
    }
    public function test_courses_capacity_schedule_attendance_and_scope():void {
        [$school,$owner,$teacher,$student]=$this->school();
        [$foreign,$foreignOwner]=$this->school();
        $this->action($student,$school,'course',['title'=>'Illegal','description'=>'No'])->assertForbidden();
        $this->action($owner,$school,'course',['title'=>'Road basics','description'=>'Safe driving'])->assertOk();
        $course=DB::table('school_courses')->value('id');
        $this->action($owner,$school,'batch',['school_course_id'=>$course,'instructor_id'=>$teacher->id,'name'=>'Morning class',
            'starts_on'=>now()->toDateString(),'ends_on'=>now()->addWeek()->toDateString(),'capacity'=>1])->assertOk();
        $batch=DB::table('school_batches')->value('id');
        $this->action($foreignOwner,$foreign,'close-batch',['batch_id'=>$batch])->assertNotFound();
        $this->action($student,$school,'join',['batch_id'=>$batch])->assertOk();
        $this->action($student,$school,'join',['batch_id'=>$batch])->assertOk();
        $second=User::factory()->create(['is_active'=>true]);$school->memberships()->create(['user_id'=>$second->id,'role'=>'Student']);
        $this->action($second,$school,'join',['batch_id'=>$batch])->assertUnprocessable();
        $payload=['batch_id'=>$batch,'starts_at'=>now('Asia/Kathmandu')->addDay()->format('Y-m-d\TH:i'),'duration'=>60,'location'=>'School'];
        $this->action($teacher,$school,'session',$payload)->assertOk();
        $this->action($teacher,$school,'session',$payload)->assertUnprocessable();
        $session=DB::table('school_class_sessions')->value('id');
        $this->action($teacher,$school,'attendance',['session_id'=>$session,'user_id'=>$student->id,'status'=>'Present'])->assertUnprocessable();
        DB::table('school_class_sessions')->where('id',$session)->update(['starts_at'=>now()->subHours(2),'ends_at'=>now()->subHour()]);
        $this->action($teacher,$school,'attendance',['session_id'=>$session,'user_id'=>$student->id,'status'=>'Present'])->assertOk();
        $this->action($teacher,$school,'attendance',['session_id'=>$session,'user_id'=>$second->id,'status'=>'Absent'])->assertNotFound();
        $this->actingAs($student)->getJson('/api/school-centre/'.$school->id)->assertOk()->assertJsonCount(1,'data.attendance');
        $this->actingAs($second)->getJson('/api/school-centre/'.$school->id)->assertOk()->assertJsonCount(0,'data.attendance')->assertJsonCount(0,'data.sessions');
        $this->actingAs($owner)->get(route('learn.school.partner',$school->id))->assertOk()->assertSee('Morning class');
        $this->actingAs($student)->get(route('learn.school.partner',$school->id))->assertOk()->assertSee('Learning progress privacy');
        $this->actingAs($foreignOwner)->getJson('/api/school-centre/'.$school->id)->assertNotFound();
        $this->action($student,$school,'consent',['share_progress'=>true])->assertOk();
        $this->assertDatabaseHas('school_memberships',['user_id'=>$student->id,'share_progress'=>true]);
        $this->action($owner,$school,'consent',['share_progress'=>true])->assertForbidden();
    }
    public function test_progress_requires_consent_and_never_exposes_answers():void {
        [$school,$owner,$teacher,$student]=$this->school();
        \App\Models\LearnerPracticeAttempt::create(['user_id'=>$student->id,'questions'=>[['text'=>'private question']],
            'answers'=>['private answer'],'score'=>1,'expires_at'=>now()->addHour(),'completed_at'=>now()]);
        $this->actingAs($owner)->getJson('/api/school-centre/'.$school->id)->assertJsonCount(0,'data.progress')->assertDontSee('private answer');
        $this->action($student,$school,'consent',['share_progress'=>true])->assertOk();
        $this->actingAs($owner)->getJson('/api/school-centre/'.$school->id)->assertJsonPath('data.progress.0.completed_attempts',1)->assertDontSee('private answer')->assertDontSee('private question');
        $this->action($student,$school,'consent',['share_progress'=>false])->assertOk();
        $this->actingAs($owner)->getJson('/api/school-centre/'.$school->id)->assertJsonCount(0,'data.progress');
    }
    public function test_mobile_token_access_obeys_membership_and_account_status():void {
        [$school,$owner,$teacher,$student]=$this->school();
        $this->getJson('/api/school-centre')->assertUnauthorized();
        $token=$student->createToken('school-test')->plainTextToken;
        $this->withHeader('Authorization','Bearer '.$token)->getJson('/api/school-centre/'.$school->id)->assertOk()->assertJsonPath('data.member.role','Student');
        $this->postJson('/api/school-centre/'.$school->id.'/course',['title'=>'Denied','description'=>'Denied'])->assertForbidden();
        $school->update(['status'=>'Suspended']);
        $this->getJson('/api/school-centre/'.$school->id)->assertNotFound();
    }
    public function test_weekly_schedule_is_atomic_and_teacher_must_remain_eligible():void {
        [$school,$owner,$teacher,$student]=$this->school();
        $course=DB::table('school_courses')->insertGetId(['driving_school_id'=>$school->id,'title'=>'Course','description'=>'Basics']);
        $batch=DB::table('school_batches')->insertGetId(['school_course_id'=>$course,'instructor_id'=>$teacher->id,'name'=>'Weekly',
            'starts_on'=>now()->toDateString(),'ends_on'=>now()->addDays(10)->toDateString(),'capacity'=>10]);
        $payload=['batch_id'=>$batch,'starts_at'=>now('Asia/Kathmandu')->addDay()->format('Y-m-d\TH:i'),'duration'=>60,'location'=>'School','weeks'=>3];
        $this->action($teacher,$school,'session',$payload)->assertUnprocessable();
        $this->assertDatabaseCount('school_class_sessions',0);
        $payload['weeks']=2;$this->action($teacher,$school,'session',$payload)->assertOk();
        $this->assertDatabaseCount('school_class_sessions',2);
        $school->memberships()->where('user_id',$teacher->id)->update(['role'=>'Student']);
        $payload['starts_at']=now('Asia/Kathmandu')->addDays(2)->format('Y-m-d\TH:i');$payload['weeks']=1;
        $this->action($owner,$school,'session',$payload)->assertUnprocessable();
        $this->assertDatabaseCount('school_class_sessions',2);
    }
}
