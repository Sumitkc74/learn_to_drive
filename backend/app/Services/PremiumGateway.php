<?php
namespace App\Services;
use App\Models\{PremiumPayment,User};
use Illuminate\Support\Facades\{Http,DB};
class PremiumGateway {
    public function ready(string $gateway):bool {
        return config('payments.enabled') && config('payments.amount_paisa')>=1000 && config('payments.days')>0
            && (config('payments.live') || !app()->environment('production'))
            && match($gateway){'khalti'=>(bool)config('payments.khalti_key'),'esewa'=>(bool)(config('payments.esewa_code') && config('payments.esewa_secret')),default=>false};
    }
    private function http(){return Http::connectTimeout(10)->timeout(25)->withOptions(['allow_redirects'=>false,'verify'=>config('official-content.ca_bundle') ?: true]);}
    private function khaltiBase($live){return $live?'https://khalti.com/api/v2':'https://dev.khalti.com/api/v2';}
    private function esewaBase($live){return $live?'https://epay.esewa.com.np':'https://rc-epay.esewa.com.np';}
    public function startKhalti(PremiumPayment $payment):string {
        $response=$this->http()->withHeaders(['Authorization'=>'Key '.config('payments.khalti_key')])->post($this->khaltiBase($payment->live).'/epayment/initiate/',[
            'return_url'=>route('learn.payments.return',$payment),'website_url'=>url('/'),
            'amount'=>$payment->amount_paisa,'purchase_order_id'=>$payment->id,'purchase_order_name'=>'Learn to Drive Premium - '.$payment->access_days.' days',
        ]);
        $url=$response->json('payment_url');$id=$response->json('pidx');
        if(!$response->successful() || !is_string($id) || !$id || !is_string($url) || parse_url($url,PHP_URL_SCHEME)!=='https' || !in_array(parse_url($url,PHP_URL_HOST),['pay.khalti.com','test-pay.khalti.com']))throw new \RuntimeException('Checkout unavailable.');
        $payment->update(['provider_id'=>$id]);return $url;
    }
    public function esewaForm(PremiumPayment $p):array {
        $amount=number_format($p->amount_paisa/100,2,'.','');
        $fields=['amount'=>$amount,'tax_amount'=>'0','total_amount'=>$amount,'transaction_uuid'=>$p->id,'product_code'=>$p->merchant,'product_service_charge'=>'0','product_delivery_charge'=>'0','success_url'=>route('learn.payments.return',$p),'failure_url'=>route('learn.payments.show',$p),'signed_field_names'=>'total_amount,transaction_uuid,product_code'];
        $fields['signature']=base64_encode(hash_hmac('sha256','total_amount='.$amount.',transaction_uuid='.$p->id.',product_code='.$p->merchant,config('payments.esewa_secret'),true));
        return ['action'=>$this->esewaBase($p->live).'/api/epay/main/v2/form','fields'=>$fields];
    }
    public function verify(PremiumPayment $p):bool {
        if($p->status==='Paid')return true;
        if($p->live!==(bool)config('payments.live'))throw new \RuntimeException('Environment mismatch.');
        if(!$p->live && app()->environment('production'))throw new \RuntimeException('Test checkout is disabled in production.');
        if($p->gateway==='khalti'){
            if(!$p->provider_id)return false;
            $r=$this->http()->withHeaders(['Authorization'=>'Key '.config('payments.khalti_key')])->post($this->khaltiBase($p->live).'/epayment/lookup/',['pidx'=>$p->provider_id]);
            if(!$r->successful())throw new \RuntimeException('Lookup failed.');
            $d=$r->json();
            if(($d['status'] ?? '')!=='Completed' || ($d['pidx'] ?? '')!==$p->provider_id || ($d['refunded'] ?? false))return false;
            if((string)($d['total_amount'] ?? '')!==(string)$p->amount_paisa)throw new \RuntimeException('Amount mismatch.');
            $transaction=$d['transaction_id'] ?? null;
        }else{
            $base=$p->live?'https://epay.esewa.com.np':'https://rc.esewa.com.np';
            $r=$this->http()->get($base.'/api/epay/transaction/status/',['product_code'=>$p->merchant,'total_amount'=>number_format($p->amount_paisa/100,2,'.',''),'transaction_uuid'=>$p->id]);
            if(!$r->successful())throw new \RuntimeException('Lookup failed.');$d=$r->json();
            if(($d['status'] ?? '')!=='COMPLETE')return false;
            if(($d['transaction_uuid'] ?? '')!==$p->id || ($d['product_code'] ?? '')!==$p->merchant || !is_numeric($d['total_amount'] ?? null) || (int)round((float)$d['total_amount']*100)!==$p->amount_paisa)throw new \RuntimeException('Payment mismatch.');
            $transaction=$d['ref_id'] ?? null;
        }
        if(!is_string($transaction) || $transaction==='')throw new \RuntimeException('Missing transaction.');
        DB::transaction(function()use($p,$transaction){
            $payment=PremiumPayment::lockForUpdate()->findOrFail($p->id);
            if($payment->status==='Paid')return;
            $user=User::lockForUpdate()->findOrFail($payment->user_id);
            $until=($user->premium_until?->isFuture()?$user->premium_until->copy():now())->addDays($payment->access_days);
            $user->forceFill(['premium_until'=>$until])->save();
            $payment->update(['status'=>'Paid','transaction_id'=>$transaction,'paid_at'=>now(),'access_until'=>$until]);
        });return true;
    }
}
