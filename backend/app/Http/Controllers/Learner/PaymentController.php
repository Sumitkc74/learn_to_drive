<?php
namespace App\Http\Controllers\Learner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\PremiumPayment;
use App\Services\PremiumGateway;
class PaymentController extends \App\Http\Controllers\Controller {
    public function index(Request $r){return view('learner.payments',['payments'=>PremiumPayment::where('user_id',$r->user()->id)->latest()->paginate(10)]);}
    public function checkout(Request $r,PremiumGateway $gateway){
        $data=$r->validate(['gateway'=>'required|in:khalti,esewa']);
        abort_unless($gateway->ready($data['gateway']),503,__('This payment option is not configured.'));
        $p=PremiumPayment::create(['id'=>(string)Str::uuid(),'user_id'=>$r->user()->id,'gateway'=>$data['gateway'],'live'=>config('payments.live'),'merchant'=>$data['gateway']==='esewa'?config('payments.esewa_code'):null,'amount_paisa'=>config('payments.amount_paisa'),'access_days'=>config('payments.days')]);
        try {
            if($p->gateway==='khalti')return redirect()->away($gateway->startKhalti($p));
            return response()->view('learner.checkout',['payment'=>$p]+$gateway->esewaForm($p))->header('Cache-Control','private, no-store');
        }catch(\Throwable $e){$p->update(['status'=>'Initiation failed']);return redirect()->route('learn.payments.show',$p)->with('error',__('Checkout could not start. No Premium access has been added.'));}
    }
    public function show(Request $r,string $id){return view('learner.payment',['payment'=>PremiumPayment::where('user_id',$r->user()->id)->findOrFail($id)]);}
    public function verify(Request $r,string $id,PremiumGateway $gateway){
        $p=PremiumPayment::where('user_id',$r->user()->id)->findOrFail($id);
        try{$paid=$gateway->verify($p);}catch(\Throwable $e){return redirect()->route('learn.payments.show',$p)->with('error',__('Payment could not be verified yet. If money was deducted, do not pay again; check the status later or contact support.'));}
        return redirect()->route('learn.payments.show',$p)->with($paid?'success':'error',$paid?__('Payment verified. Premium access is active.'):__('Payment is not confirmed yet. Check again later if money was deducted.'));
    }
}
