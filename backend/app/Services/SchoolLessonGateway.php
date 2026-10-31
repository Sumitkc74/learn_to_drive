<?php
namespace App\Services;
use App\Models\SchoolLessonPayment;
use Illuminate\Support\Facades\{Http,DB};

class SchoolLessonGateway {
    private function http(){return Http::connectTimeout(10)->timeout(25)->withOptions(['allow_redirects'=>false,'verify'=>config('official-content.ca_bundle') ?: true]);}
    private function khalti(bool $live):string{return $live?'https://khalti.com/api/v2':'https://dev.khalti.com/api/v2';}
    public function ready(string $gateway):bool {
        return config('schools.payments_enabled') && (config('schools.payments_live') || !app()->environment('production'))
            && (bool)match($gateway){'khalti'=>config('payments.khalti_key'),'esewa'=>config('payments.esewa_code') && config('payments.esewa_secret'),default=>false};
    }
    public function start(SchoolLessonPayment $p):array {
        if(!$this->ready($p->gateway) || $p->live!==(bool)config('schools.payments_live'))throw new \RuntimeException('School checkout unavailable.');
        if($p->gateway==='khalti'){
            if($p->checkout_url)return ['url'=>$p->checkout_url];
            $r=$this->http()->withHeaders(['Authorization'=>'Key '.config('payments.khalti_key')])->post($this->khalti($p->live).'/epayment/initiate/',[
                'return_url'=>route('learn.lesson-payment.show',$p->id),'website_url'=>url('/'),'amount'=>$p->amount_paisa,
                'purchase_order_id'=>$p->id,'purchase_order_name'=>'Private driving lesson',
            ]);
            $url=$r->json('payment_url');$id=$r->json('pidx');
            if(!$r->successful() || !is_string($id) || $id==='' || !is_string($url) || parse_url($url,PHP_URL_SCHEME)!=='https'
                || parse_url($url,PHP_URL_USER)!==null || !in_array(parse_url($url,PHP_URL_HOST),['pay.khalti.com','test-pay.khalti.com'],true))throw new \RuntimeException('School checkout unavailable.');
            $p->update(['provider_id'=>$id,'checkout_url'=>$url]);return ['url'=>$url];
        }
        $amount=number_format($p->amount_paisa/100,2,'.','');
        $fields=['amount'=>$amount,'tax_amount'=>'0','total_amount'=>$amount,'transaction_uuid'=>$p->id,'product_code'=>$p->merchant,
            'product_service_charge'=>'0','product_delivery_charge'=>'0','success_url'=>route('learn.lesson-payment.show',$p->id),
            'failure_url'=>route('learn.lesson-payment.show',$p->id),'signed_field_names'=>'total_amount,transaction_uuid,product_code'];
        $fields['signature']=base64_encode(hash_hmac('sha256','total_amount='.$amount.',transaction_uuid='.$p->id.',product_code='.$p->merchant,config('payments.esewa_secret'),true));
        return ['action'=>($p->live?'https://epay.esewa.com.np':'https://rc-epay.esewa.com.np').'/api/epay/main/v2/form','fields'=>$fields];
    }
    public function verify(SchoolLessonPayment $p):bool {
        if(in_array($p->status,['Paid','RefundRequested','Refunded'],true))return true;
        if(!$this->ready($p->gateway) || $p->live!==(bool)config('schools.payments_live'))throw new \RuntimeException('School verification unavailable.');
        if($p->gateway==='khalti'){
            if(!$p->provider_id)return false;
            $r=$this->http()->withHeaders(['Authorization'=>'Key '.config('payments.khalti_key')])->post($this->khalti($p->live).'/epayment/lookup/',['pidx'=>$p->provider_id]);
            if(!$r->successful())throw new \RuntimeException('Payment verification failed.');
            $d=$r->json();
            if(($d['status']??'')!=='Completed' || ($d['pidx']??'')!==$p->provider_id || ($d['refunded']??false))return false;
            if((string)($d['total_amount']??'')!==(string)$p->amount_paisa)throw new \RuntimeException('Payment amount mismatch.');
            $transaction=$d['transaction_id']??null;
        }else{
            $r=$this->http()->get(($p->live?'https://epay.esewa.com.np':'https://rc.esewa.com.np').'/api/epay/transaction/status/',[
                'product_code'=>$p->merchant,'total_amount'=>number_format($p->amount_paisa/100,2,'.',''),'transaction_uuid'=>$p->id]);
            if(!$r->successful())throw new \RuntimeException('Payment verification failed.');$d=$r->json();
            if(($d['status']??'')!=='COMPLETE')return false;
            if(($d['transaction_uuid']??'')!==$p->id || ($d['product_code']??'')!==$p->merchant || !is_numeric($d['total_amount']??null)
                || (int)round((float)$d['total_amount']*100)!==$p->amount_paisa)throw new \RuntimeException('Payment amount mismatch.');
            $transaction=$d['ref_id']??null;
        }
        if(!is_string($transaction) || $transaction==='')throw new \RuntimeException('Missing transaction reference.');
        DB::transaction(function()use($p,$transaction){
            $booking=\App\Models\LessonBooking::whereKey($p->lesson_booking_id)->lockForUpdate()->firstOrFail();
            $locked=SchoolLessonPayment::whereKey($p->id)->lockForUpdate()->firstOrFail();
            if($locked->status!=='Pending')return;
            $locked->update(['transaction_id'=>$transaction,'status'=>$booking->status==='Booked'?'Paid':'RefundRequested','paid_at'=>now()]);
            SchoolNotifications::send($booking->learner_id,'Your school lesson payment was verified. Check your booking receipt.');
        });return true;
    }
}
