<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use App\Models\{User,PremiumPayment};
class ApiPaymentInitiationTest extends TestCase {
    use RefreshDatabase;
    protected function setUp():void {parent::setUp();config(['payments.enabled'=>true,'payments.live'=>false,'payments.amount_paisa'=>50000,'payments.days'=>0,'payments.lifetime'=>true,'payments.khalti_key'=>'test-key','payments.esewa_code'=>'TEST','payments.esewa_secret'=>'test-secret']);Http::preventStrayRequests();}
    public function test_api_starts_khalti_and_returns_payment_url():void {
        $user=User::factory()->create(['is_active'=>true]);Sanctum::actingAs($user);
        Http::fake(['*'=>Http::response(['pidx'=>'created-id','payment_url'=>'https://test-pay.khalti.com/checkout/test'])]);
        $this->postJson('/api/payment',['gateway'=>'khalti','amount_paisa'=>1])->assertOk()
            ->assertJsonPath('gateway','khalti')->assertJsonPath('amount_paisa',50000)->assertJsonPath('payment_url','https://test-pay.khalti.com/checkout/test');
        $p=PremiumPayment::firstOrFail();
        $this->assertSame('created-id',$p->provider_id);$this->assertSame('Pending',$p->status);$this->assertSame(0,$p->access_days);
    }
    public function test_api_starts_esewa_and_returns_signed_form():void {
        $user=User::factory()->create(['is_active'=>true]);Sanctum::actingAs($user);
        $response=$this->postJson('/api/payment',['gateway'=>'esewa'])->assertOk()
            ->assertJsonPath('gateway','esewa')->assertJsonPath('amount_paisa',50000)
            ->assertJsonPath('action','https://rc-epay.esewa.com.np/api/epay/main/v2/form')
            ->assertJsonPath('fields.product_code','TEST')->assertJsonPath('fields.total_amount','500.00');
        $p=PremiumPayment::firstOrFail();
        $expected=base64_encode(hash_hmac('sha256','total_amount=500.00,transaction_uuid='.$p->id.',product_code=TEST','test-secret',true));
        $this->assertSame($expected,$response->json('fields.signature'));
        $this->assertSame('TEST',$p->merchant);
    }
    public function test_api_initiation_gates_unconfigured_and_invalid_gateway():void {
        $user=User::factory()->create(['is_active'=>true]);Sanctum::actingAs($user);
        $this->postJson('/api/payment',['gateway'=>'bogus'])->assertStatus(422);
        config(['payments.enabled'=>false]);
        $this->postJson('/api/payment',['gateway'=>'khalti'])->assertStatus(503);
        $this->assertSame(0,PremiumPayment::count());
    }
    public function test_api_initiation_failure_is_recorded():void {
        $user=User::factory()->create(['is_active'=>true]);Sanctum::actingAs($user);
        Http::fake(['*'=>Http::response(null,500)]);
        $this->postJson('/api/payment',['gateway'=>'khalti'])->assertStatus(503);
        $this->assertSame('Initiation failed',PremiumPayment::firstOrFail()->status);
    }
    public function test_api_requires_authentication():void {
        $this->postJson('/api/payment',['gateway'=>'khalti'])->assertStatus(401);
    }
}
