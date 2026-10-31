<?php
namespace App\Http\Controllers\Learner;
use App\Http\Controllers\Controller;
use App\Models\{LessonBooking,SchoolLessonPayment};
use App\Services\SchoolLessonGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Cache};
use Illuminate\Support\Str;
class SchoolPaymentController extends Controller {
    public function checkout(Request $r,int $booking){
        $data=$r->validate(['gateway'=>'required|in:khalti,esewa']);$gateway=app(SchoolLessonGateway::class);
        abort_unless($gateway->ready($data['gateway']),422,__('Online lesson payments are not configured.'));
        $p=DB::transaction(function()use($r,$booking,$data){
            $b=LessonBooking::where('learner_id',$r->user()->id)->lockForUpdate()->findOrFail($booking);
            abort_unless($b->status==='Booked' && $b->lesson->starts_at->isFuture() && \App\Models\DrivingLesson::availableProvider()->whereKey($b->driving_lesson_id)->exists(),422);
            abort_if($b->lesson->price_npr<10,422);
            $p=SchoolLessonPayment::where('lesson_booking_id',$b->id)->first();
            if(!$p){ $p=new SchoolLessonPayment; $p->forceFill(['id'=>(string)Str::uuid(),'lesson_booking_id'=>$b->id,'amount_paisa'=>$b->lesson->price_npr*100,'gateway'=>$data['gateway'],'live'=>(bool)config('schools.payments_live'),'merchant'=>$data['gateway']==='esewa'?config('payments.esewa_code'):null])->save(); }
            return $p;
        });
        return redirect()->route('learn.lesson-payment.show',$p->id);
    }
    public function show(Request $r,string $payment){
        $p=SchoolLessonPayment::whereHas('booking',fn($q)=>$q->where('learner_id',$r->user()->id))->findOrFail($payment);
        // Rendering a receipt never initiates or verifies a payment.
        return view('learner.lessons.receipt',['payment'=>$p]);
    }
    public function start(Request $r,string $payment){
        $p=SchoolLessonPayment::whereHas('booking',fn($q)=>$q->where('learner_id',$r->user()->id))->findOrFail($payment);
        abort_unless($p->status==='Pending' && $p->booking->status==='Booked' && $p->booking->lesson->starts_at->isFuture(),422);
        abort_unless(\App\Models\DrivingLesson::availableProvider()->whereKey($p->booking->driving_lesson_id)->where('cancelled',false)->exists(),422);
        try { $checkout=Cache::lock('school-checkout-'.$p->id,40)->block(2,fn()=>app(SchoolLessonGateway::class)->start($p->fresh())); }
        catch(\Throwable $e){report($e);return back()->with('error',__('School checkout is unavailable. Try again later.'));}
        if(isset($checkout['url']))return redirect()->away($checkout['url']);
        return view('learner.lessons.checkout',compact('checkout'));
    }
    public function verify(Request $r,string $payment){
        $p=SchoolLessonPayment::whereHas('booking',fn($q)=>$q->where('learner_id',$r->user()->id))->findOrFail($payment);
        try { $paid=app(SchoolLessonGateway::class)->verify($p); }
        catch(\Throwable $e){report($e);return back()->with('error',__('Payment verification is unavailable. Try again later.'));}
        return back()->with($paid?'success':'error',__($paid?'Payment verified.':'Payment is not confirmed yet.'));
    }
}
