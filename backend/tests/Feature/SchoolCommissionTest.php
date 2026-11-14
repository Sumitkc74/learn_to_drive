<?php
namespace Tests\Feature;
use App\Models\{DrivingSchool,DrivingLesson,LessonBooking,LessonVehicle,SchoolJoinRequest,SchoolLessonPayment,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http};
use Tests\TestCase;
class SchoolCommissionTest extends TestCase {
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
    private function booking():array {
        config(['schools.payments_enabled'=>true,'schools.payments_live'=>false,'schools.commission_percent'=>10,'payments.khalti_key'=>'test-secret','payments.esewa_code'=>'EPAYTEST','payments.esewa_secret'=>'test-secret']);
        Http::preventStrayRequests();
        $user=User::factory()->create(['is_active'=>true]);$teacher=User::factory()->create(['is_active'=>true]);
        $school=DrivingSchool::create(['name'=>'Payment School','status'=>'Approved']);
        $school->memberships()->create(['user_id'=>$teacher->id,'role'=>'Instructor']);
        $car=LessonVehicle::create(['driving_school_id'=>$school->id,'instructor_id'=>$teacher->id,'name'=>'Car','registration'=>'TEST-1','transmission'=>'Manual']);
        $lesson=DrivingLesson::create(['lesson_vehicle_id'=>$car->id,'starts_at'=>now()->addDay(),'ends_at'=>now()->addDay()->addHour(),'price_npr'=>1500,'meeting_point'=>'School']);
        $booking=LessonBooking::create(['driving_lesson_id'=>$lesson->id,'learner_id'=>$user->id,'active_lesson_id'=>$lesson->id]);
        $this->actingAs($user)->post(route('learn.lesson-payment.checkout',$booking->id),['gateway'=>'khalti','amount'=>1])->assertRedirect();
        return [$user,$booking,SchoolLessonPayment::firstOrFail()];
    }
    private function paidPayment(DrivingSchool $school,int $amount,int $commission,int $payout,bool $live): SchoolLessonPayment {
        $teacher=User::factory()->create(['is_active'=>true]);$learner=User::factory()->create(['is_active'=>true]);
        $car=LessonVehicle::create(['driving_school_id'=>$school->id,'instructor_id'=>$teacher->id,'name'=>'Car','registration'=>'TEST-'.$teacher->id,'transmission'=>'Manual']);
        $lesson=DrivingLesson::create(['lesson_vehicle_id'=>$car->id,'starts_at'=>now()->addDay(),'ends_at'=>now()->addDay()->addHour(),'price_npr'=>1000,'meeting_point'=>'School']);
        $booking=LessonBooking::create(['driving_lesson_id'=>$lesson->id,'learner_id'=>$learner->id,'active_lesson_id'=>$lesson->id]);
        $p=new SchoolLessonPayment; $p->forceFill(['id'=>(string)\Illuminate\Support\Str::uuid(),'lesson_booking_id'=>$booking->id,'amount_paisa'=>$amount,'gateway'=>'khalti','live'=>$live,'status'=>'Paid','commission_paisa'=>$commission,'school_payout_paisa'=>$payout,'paid_at'=>now()])->save();
        return $p;
    }
    public function test_verified_lesson_payment_records_commission_and_payout():void {
        [$user,$booking,$p]=$this->booking();$p->update(['provider_id'=>'pidx-test']);
        Http::fake(['*/epayment/lookup/'=>Http::response(['status'=>'Completed','pidx'=>'pidx-test','total_amount'=>150000,'transaction_id'=>'txn-test','refunded'=>false])]);
        $this->post(route('learn.lesson-payment.verify',$p->id))->assertSessionHas('success');
        $this->assertSame('Paid',$p->fresh()->status);
        $this->assertSame(15000,$p->fresh()->commission_paisa);
        $this->assertSame(135000,$p->fresh()->school_payout_paisa);
        // A later refund keeps the recorded split for history.
        $this->delete(route('learn.lessons.cancel',$booking->id))->assertRedirect();
        $this->assertSame('RefundRequested',$p->fresh()->status);
        $this->assertSame(15000,$p->fresh()->commission_paisa);
        $this->assertSame(135000,$p->fresh()->school_payout_paisa);
    }
    public function test_report_includes_commission_payout_and_enrollment_fees():void {
        [$school,$owner,$teacher,$student]=$this->school();
        $this->paidPayment($school,150000,15000,135000,true);
        $this->paidPayment($school,100000,10000,90000,false);
        $refunded=$this->paidPayment($school,50000,5000,45000,true);
        SchoolLessonPayment::whereKey($refunded->id)->update(['status'=>'RefundRequested']);
        $discovery=User::factory()->create(['is_active'=>true]);
        $school->memberships()->create(['user_id'=>$discovery->id,'role'=>'Student','enrollment_source'=>'discovery','enrollment_fee_paisa'=>10000]);
        $schoolAdded=User::factory()->create(['is_active'=>true]);
        $school->memberships()->create(['user_id'=>$schoolAdded->id,'role'=>'Student','enrollment_source'=>'school','enrollment_fee_paisa'=>0]);
        $this->actingAs($owner)->getJson('/api/school-centre/'.$school->id.'/report')->assertOk()
            ->assertJsonPath('data.metrics.Platform commission — live (NPR)',150)
            ->assertJsonPath('data.metrics.School payout — live (NPR)',1350)
            ->assertJsonPath('data.metrics.Platform commission — test (NPR)',100)
            ->assertJsonPath('data.metrics.School payout — test (NPR)',900)
            ->assertJsonPath('data.metrics.Enrollment fees owed (NPR)',100);
    }
    public function test_discovery_join_request_approve_enrols_student_with_fee():void {
        config(['schools.enrollment_fee_paisa'=>10000]);
        [$school,$owner,$teacher,$student]=$this->school();
        $learner=User::factory()->create(['is_active'=>true]);
        $this->actingAs($learner)->get(route('learn.schools'))->assertOk()->assertSee('Request to join');
        $this->actingAs($learner)->post(route('learn.schools.join-request',$school->id))->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('school_join_requests',['driving_school_id'=>$school->id,'user_id'=>$learner->id,'status'=>'Pending']);
        $id=SchoolJoinRequest::value('id');
        $this->actingAs($owner)->get(route('learn.school.partner',$school->id))->assertOk()->assertSee('Join requests')->assertSee($learner->name);
        $this->actingAs($owner)->postJson('/api/school-centre/'.$school->id.'/join-approve',['request_id'=>$id])->assertOk();
        $this->assertDatabaseHas('school_memberships',['driving_school_id'=>$school->id,'user_id'=>$learner->id,'role'=>'Student','enrollment_source'=>'discovery','enrollment_fee_paisa'=>10000]);
        $this->assertDatabaseHas('school_join_requests',['id'=>$id,'status'=>'Approved']);
        $this->assertDatabaseHas('school_notifications',['user_id'=>$learner->id]);
        $this->actingAs($learner)->get(route('learn.schools'))->assertOk()->assertDontSee('Request to join');
    }
    public function test_discovery_join_request_decline_duplicates_and_non_approved():void {
        [$school,$owner,$teacher,$student]=$this->school();
        $learner=User::factory()->create(['is_active'=>true]);
        $this->actingAs($learner)->postJson(route('learn.schools.join-request',$school->id))->assertOk();
        $id=SchoolJoinRequest::value('id');
        $this->actingAs($learner)->postJson(route('learn.schools.join-request',$school->id))->assertUnprocessable();
        $this->actingAs($owner)->postJson('/api/school-centre/'.$school->id.'/join-decline',['request_id'=>$id])->assertOk();
        $this->assertDatabaseHas('school_join_requests',['id'=>$id,'status'=>'Declined']);
        $this->assertDatabaseMissing('school_memberships',['user_id'=>$learner->id]);
        $this->actingAs($learner)->postJson(route('learn.schools.join-request',$school->id))->assertOk();
        $this->actingAs($student)->postJson(route('learn.schools.join-request',$school->id))->assertUnprocessable();
        $pending=DrivingSchool::create(['name'=>'Pending School','status'=>'Pending']);
        $this->actingAs($learner)->postJson(route('learn.schools.join-request',$pending->id))->assertNotFound();
    }
    public function test_staff_invite_and_create_student_record_school_source():void {
        [$school,$owner,$teacher,$student]=$this->school();
        $existing=User::factory()->create(['is_active'=>true]);
        $this->actingAs($owner)->postJson('/api/school-centre/'.$school->id.'/invite',['email'=>$existing->email,'role'=>'Student'])->assertOk();
        $id=DB::table('school_invitations')->value('id');
        $this->actingAs($existing)->postJson('/api/school-centre/invitations/'.$id,['decision'=>'accept'])->assertOk();
        $this->assertDatabaseHas('school_memberships',['driving_school_id'=>$school->id,'user_id'=>$existing->id,'role'=>'Student','enrollment_source'=>'school','enrollment_fee_paisa'=>0]);
        $this->actingAs($owner)->postJson('/api/school-centre/'.$school->id.'/create-student',['name'=>'New Student','email'=>'newstudent@example.com','phoneNumber'=>'9800000001','password'=>'initial-pass-1','password_confirmation'=>'initial-pass-1'])->assertOk();
        $created=User::where('email','newstudent@example.com')->firstOrFail();
        $this->assertDatabaseHas('school_memberships',['driving_school_id'=>$school->id,'user_id'=>$created->id,'role'=>'Student','enrollment_source'=>'school','enrollment_fee_paisa'=>0]);
    }
    public function test_school_student_gets_premium_while_enrolled():void {
        [$school,$owner,$teacher,$student]=$this->school();
        $this->actingAs($student)->get(route('learn.premium.modules'))->assertOk();
        $this->actingAs($student)->get(route('learn.premium.chat'))->assertOk();
        $this->actingAs($student)->get(route('learn.premium'))->assertOk()->assertSee('Premium access active');
        $pending=DrivingSchool::create(['name'=>'Pending School','status'=>'Pending']);
        $pendingStudent=User::factory()->create(['is_active'=>true]);
        $pending->memberships()->create(['user_id'=>$pendingStudent->id,'role'=>'Student']);
        $this->actingAs($pendingStudent)->get(route('learn.premium.modules'))->assertRedirect(route('learn.premium'));
        $this->actingAs($pendingStudent)->get(route('learn.premium.chat'))->assertRedirect(route('learn.premium'));
        $school->memberships()->where('user_id',$student->id)->delete();
        $this->actingAs($student)->get(route('learn.premium.modules'))->assertRedirect(route('learn.premium'));
    }
}
