<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\{User,PremiumPayment};
use App\Services\PremiumGateway;
class PremiumPaymentTest extends TestCase {
    use RefreshDatabase;
    protected function setUp():void {parent::setUp();config(['payments.enabled'=>true,'payments.live'=>false,'payments.amount_paisa'=>49900,'payments.days'=>30,'payments.khalti_key'=>'test-key','payments.esewa_code'=>'TEST','payments.esewa_secret'=>'test-secret']);}
    private function order($user,$gateway='khalti') {return PremiumPayment::create(['id'=>(string)Str::uuid(),'user_id'=>$user->id,'gateway'=>$gateway,'live'=>false,'merchant'=>'TEST','amount_paisa'=>49900,'access_days'=>30,'provider_id'=>$gateway==='khalti'?'pidx-test':null]);}
    public function test_checkout_uses_server_price_and_gates_unconfigured_payment():void {
        $user=User::factory()->create(['is_active'=>true]);$this->actingAs($user);
        Http::fake(['*'=>Http::response(['pidx'=>'created-id','payment_url'=>'https://test-pay.khalti.com/checkout/test'])]);
        $this->post(route('learn.payments.checkout'),['gateway'=>'khalti','amount_paisa'=>1,'access_days'=>9999])->assertRedirect('https://test-pay.khalti.com/checkout/test');
        $this->assertSame(49900,PremiumPayment::first()->amount_paisa);
        Http::assertSent(fn($r)=>$r['amount']===49900);
        $this->post(route('learn.payments.checkout'),['gateway'=>'esewa'])->assertOk()->assertSee('signature')->assertDontSee('test-secret');
        config(['payments.enabled'=>false]);
        $this->post(route('learn.payments.checkout'),['gateway'=>'khalti'])->assertStatus(503);
    }
    public function test_khalti_grant_is_verified_owned_idempotent_and_expires():void {
        $user=User::factory()->create(['is_active'=>true]);$p=$this->order($user);
        Http::fake(['*'=>Http::response(['pidx'=>$p->provider_id,'status'=>'Completed','total_amount'=>49900,'transaction_id'=>'txn-one','refunded'=>false])]);
        $this->actingAs(User::factory()->create(['is_active'=>true]))->post(route('learn.payments.verify',$p))->assertNotFound();
        $this->actingAs($user)->get(route('learn.payments.return',$p).'?status=Completed&amount=1')->assertRedirect();
        $until=$user->fresh()->premium_until;
        $this->assertTrue($user->fresh()->hasPremium());$this->assertSame('Paid',$p->fresh()->status);
        $this->post(route('learn.payments.verify',$p))->assertRedirect();
        $this->assertTrue($until->equalTo($user->fresh()->premium_until));
        $this->travel(31)->days();$this->assertFalse($user->fresh()->hasPremium());
    }
    public function test_esewa_lookup_must_match_order_merchant_amount_and_complete_status():void {
        $user=User::factory()->create(['is_active'=>true]);$p=$this->order($user,'esewa');
        Http::fake(['*'=>Http::sequence()->push(['status'=>'COMPLETE','transaction_uuid'=>$p->id,'product_code'=>'TEST','total_amount'=>1,'ref_id'=>'ref-one'])->push(['status'=>'PENDING'])->push(['status'=>'COMPLETE','transaction_uuid'=>$p->id,'product_code'=>'TEST','total_amount'=>499,'ref_id'=>'ref-one'])]);
        $this->actingAs($user)->get(route('learn.payments.return',$p).'?data=forged')->assertSessionHas('error');
        $this->assertNull($user->fresh()->premium_until);
        $this->post(route('learn.payments.verify',$p))->assertSessionHas('error');
        $this->assertSame('Pending',$p->fresh()->status);
        $this->post(route('learn.payments.verify',$p))->assertSessionHas('success');
        $this->assertTrue($user->fresh()->hasPremium());
        $form=app(PremiumGateway::class)->esewaForm($p);
        $expected=base64_encode(hash_hmac('sha256','total_amount=499.00,transaction_uuid='.$p->id.',product_code=TEST','test-secret',true));
        $this->assertSame($expected,$form['fields']['signature']);
    }
    public function test_admin_can_refund_paid_premium_and_revoke_access():void {
        $admin=User::factory()->create(['role'=>'Admin','is_active'=>true]);
        $user=User::factory()->create(['is_active'=>true,'premium_until'=>now()->addDays(30)]);
        $p=PremiumPayment::create(['id'=>(string)Str::uuid(),'user_id'=>$user->id,'gateway'=>'khalti','live'=>false,'merchant'=>'TEST','amount_paisa'=>49900,'access_days'=>30,'status'=>'Paid','provider_id'=>'pidx-refund','transaction_id'=>'txn-refund','paid_at'=>now(),'access_until'=>now()->addDays(30)]);
        Http::fake(['https://dev.khalti.com/api/v2/refund/' => Http::response(['message' => 'Refund request successful'])]);
        $this->actingAs($admin)->post(route('admin.payments.refund',$p),['reason'=>'Customer requested refund'])->assertRedirect();
        $p=$p->fresh();
        $this->assertSame('Refunded',$p->status);
        $this->assertSame('refunded',$p->refund_status);
        $this->assertNotNull($p->refunded_at);
        $this->assertFalse($user->fresh()->hasPremium());
        $this->assertTrue($user->fresh()->premium_until->isPast());
    }

    public function test_admin_can_view_premium_payment_history_and_refund_actions():void {
        $admin=User::factory()->create(['role'=>'Admin','is_active'=>true]);
        $user=User::factory()->create(['is_active'=>true]);
        $p=PremiumPayment::create(['id'=>(string)Str::uuid(),'user_id'=>$user->id,'gateway'=>'khalti','live'=>false,'merchant'=>'TEST','amount_paisa'=>49900,'access_days'=>30,'status'=>'Paid','provider_id'=>'pidx-page','transaction_id'=>'txn-page','paid_at'=>now(),'access_until'=>now()->addDays(30)]);

        $this->actingAs($admin)
            ->get(route('admin.premium-payments.index'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee('Refund reason')
            ->assertSee(route('admin.payments.refund', $p));
    }
}
