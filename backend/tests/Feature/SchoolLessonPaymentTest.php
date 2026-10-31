<?php
namespace Tests\Feature;
use App\Models\{DrivingSchool,DrivingLesson,LessonVehicle,LessonBooking,SchoolLessonPayment,User};
use App\Services\SchoolLessonGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class SchoolLessonPaymentTest extends TestCase {
    use RefreshDatabase;
    private function booking():array {
        config(['schools.payments_enabled'=>true,'schools.payments_live'=>false,'payments.khalti_key'=>'test-secret','payments.esewa_code'=>'EPAYTEST','payments.esewa_secret'=>'test-secret']);
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
    public function test_checkout_is_owned_server_priced_and_receipt_does_not_verify():void {
        [$user,$booking,$p]=$this->booking();
        $this->assertSame(150000,$p->amount_paisa);
        $this->get(route('learn.lesson-payment.show',$p->id).'?status=Completed')->assertOk();
        Http::assertNothingSent();$this->assertSame('Pending',$p->fresh()->status);
        $other=User::factory()->create(['is_active'=>true]);
        $this->actingAs($other)->get(route('learn.lesson-payment.show',$p->id))->assertNotFound();
        $this->post(route('learn.lesson-payment.verify',$p->id))->assertNotFound();
        $this->post(route('learn.lesson-payment.checkout',$booking->id),['gateway'=>'khalti'])->assertNotFound();
        $this->actingAs($user)->get(route('learn.lessons.bookings'))->assertOk()->assertSee('Lesson receipt');
    }
    public function test_verified_payment_is_idempotent_and_cancellation_requests_manual_refund():void {
        [$user,$booking,$p]=$this->booking();$p->update(['provider_id'=>'pidx-test']);
        Http::fake(['*/epayment/lookup/'=>Http::response(['status'=>'Completed','pidx'=>'pidx-test','total_amount'=>150000,'transaction_id'=>'txn-test','refunded'=>false])]);
        $this->post(route('learn.lesson-payment.verify',$p->id))->assertSessionHas('success');
        $this->assertSame('Paid',$p->fresh()->status);
        $this->assertNull($user->fresh()->premium_until);
        $this->post(route('learn.lesson-payment.verify',$p->id))->assertSessionHas('success');Http::assertSentCount(1);
        $this->delete(route('learn.lessons.cancel',$booking->id))->assertRedirect();
        $this->assertSame('RefundRequested',$p->fresh()->status);
        $admin=User::factory()->create(['is_active'=>true,'role'=>'Admin']);
        $this->post(route('admin.school-payments.refund',$p->id),['refund_reference'=>'refund-test','confirmed'=>true])->assertForbidden();
        $this->actingAs($admin)->get(route('admin.school-payments'))->assertOk();
        $this->post(route('admin.school-payments.refund',$p->id),['refund_reference'=>'refund-test','confirmed'=>true])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Refunded',$p->fresh()->status);
    }
    public function test_mismatched_amount_and_late_payment_are_handled_safely():void {
        [$user,$booking,$p]=$this->booking();$p->update(['provider_id'=>'pidx-test']);
        Http::fake(['*/epayment/lookup/'=>Http::sequence()->push(['status'=>'Completed','pidx'=>'pidx-test','total_amount'=>1,'transaction_id'=>'txn-test'])
            ->push(['status'=>'Completed','pidx'=>'pidx-test','total_amount'=>150000,'transaction_id'=>'txn-test'])]);
        $this->post(route('learn.lesson-payment.verify',$p->id))->assertSessionHas('error');
        $this->assertSame('Pending',$p->fresh()->status);
        $this->delete(route('learn.lessons.cancel',$booking->id));
        $this->post(route('learn.lesson-payment.verify',$p->id))->assertSessionHas('success');
        $this->assertSame('RefundRequested',$p->fresh()->status);
    }
    public function test_esewa_checks_order_merchant_and_amount():void {
        [$user,$booking,$p]=$this->booking();$p->update(['gateway'=>'esewa','merchant'=>'EPAYTEST']);
        Http::fake(['*/api/epay/transaction/status/*'=>Http::response(['status'=>'COMPLETE','transaction_uuid'=>$p->id,'product_code'=>'EPAYTEST','total_amount'=>1500,'ref_id'=>'esewa-test'])]);
        $this->assertTrue(app(SchoolLessonGateway::class)->verify($p));
        $this->assertSame('Paid',$p->fresh()->status);
    }
}
