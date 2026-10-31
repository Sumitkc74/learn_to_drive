<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\SchoolLessonPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class SchoolPaymentController extends Controller {
    public function index(){return view('admin.schools.payments',['payments'=>SchoolLessonPayment::with('booking.learner','booking.lesson.vehicle.school')->latest()->paginate(25)]);}
    public function refund(Request $r,string $payment){
        $d=$r->validate(['refund_reference'=>'required|string|max:255|unique:school_lesson_payments,refund_reference','confirmed'=>'accepted']);
        DB::transaction(function()use($r,$payment,$d){
            $p=SchoolLessonPayment::whereKey($payment)->lockForUpdate()->firstOrFail();abort_unless($p->status==='RefundRequested',422);
            $p->update(['status'=>'Refunded','refund_reference'=>$d['refund_reference'],'refunded_at'=>now()]);
            \App\Models\AuditLog::create(['actor_id'=>$r->user()->id,'event'=>'school.refund_recorded','subject_type'=>SchoolLessonPayment::class,'subject_label'=>'School refund','new_values'=>['payment_id'=>$p->id,'reference'=>$d['refund_reference']]]);
            \App\Services\SchoolNotifications::send($p->booking->learner_id,'Your school lesson refund was recorded. View your receipt.');
        });
        return back()->with('success',__('Completed merchant refund recorded.'));
    }
}
