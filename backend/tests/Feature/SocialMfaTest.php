<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Services\AccountMfa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use PragmaRX\Google2FA\Google2FA;
class SocialMfaTest extends TestCase {
    use RefreshDatabase;
    public function test_mfa_setup_encryption_replay_recovery_and_access_gate(): void {
        $user=User::factory()->create(['is_active'=>true]);$this->actingAs($user);
        $this->post(route('learn.mfa.setup'),['current_password'=>'password'])->assertRedirect();
        $secret=session('mfa_setup.secret');$code=(new Google2FA)->getCurrentOtp($secret);
        $this->post(route('learn.mfa.enable'),['code'=>$code])->assertRedirect();
        $codes=session('recovery_codes');$this->assertCount(8,$codes);
        $this->assertNotSame($secret,$user->fresh()->getRawOriginal('mfa_secret'));
        $this->assertArrayNotHasKey('mfa_secret',$user->fresh()->toArray());
        $this->assertFalse(app(AccountMfa::class)->consume($user,$code));
        $this->get('/learn/settings')->assertOk()->assertSee('MFA enabled');
        $this->withSession(['mfa_verified'=>null])->get('/learn/account')->assertRedirect(route('learn.mfa.challenge'));
        $this->patch('/learn/settings/detail/name',['name'=>'Bypass'])->assertRedirect(route('learn.mfa.challenge'));
        $this->post(route('learn.mfa.verify'),['code'=>$codes[0]])->assertRedirect(route('learn.account'));
        $this->assertFalse(app(AccountMfa::class)->consume($user,$codes[0]));
        $this->delete(route('learn.mfa.disable'),['current_password'=>'password','code'=>$codes[1]])->assertRedirect();
        $this->assertNull($user->fresh()->mfa_secret);
    }
    public function test_password_login_and_api_cannot_bypass_mfa(): void {
        $user=User::factory()->create(['is_active'=>true]);
        $user->forceFill(['mfa_secret'=>(new Google2FA)->generateSecretKey()])->save();
        $this->post('/learn/login',['email'=>$user->email,'password'=>'password'])->assertRedirect(route('learn.mfa.challenge'));
        $this->get('/learn/account')->assertRedirect(route('learn.mfa.challenge'));
        $this->postJson('/api/auth/login',['email'=>$user->email,'password'=>'password'])->assertForbidden();
    }
    private function google($id,$email) {
        config(['services.google.client_id'=>'test','services.google.client_secret'=>'test','services.google.redirect'=>'http://localhost/learn/auth/google/callback']);
        $profile=(new \Laravel\Socialite\Two\User)->setRaw(['email_verified'=>true])->map(['id'=>$id,'email'=>$email,'name'=>'Google learner']);
        $provider=\Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($profile);Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->withSession(['google_flow'=>['expires'=>time()+600,'link'=>null]]);
    }
    public function test_google_does_not_automatically_link_matching_emails(): void {
        $user=User::factory()->create(['is_active'=>true]);$this->google('google-test',$user->email);
        $this->get(route('learn.google.callback'))->assertRedirect(route('learn.login'))->assertSessionHas('error');
        $this->assertGuest();$this->assertNull($user->fresh()->google_id);
    }
    public function test_google_new_account_completion_and_mfa_for_linked_account(): void {
        $this->google('google-new','google@example.test');
        $this->get(route('learn.google.callback'))->assertRedirect(route('learn.google.finish'));
        $this->get(route('learn.google.finish'))->assertOk();
        $this->post(route('learn.google.finish'),['name'=>'Learner','phoneNumber'=>'9800000000','password'=>'password-123','password_confirmation'=>'password-123'])->assertRedirect(route('learn.account'));
        $user=User::where('email','google@example.test')->firstOrFail();$this->assertTrue($user->hasVerifiedEmail());$this->assertSame('User',$user->role);
        $this->post('/learn/logout');
        $user->forceFill(['mfa_secret'=>(new Google2FA)->generateSecretKey()])->save();
        $this->google('google-new','google@example.test');
        $this->get(route('learn.google.callback'))->assertRedirect(route('learn.mfa.challenge'));
    }
}
