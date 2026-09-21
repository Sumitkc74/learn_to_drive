<?php
namespace Tests\Feature;

use App\Models\{User,Question};
use App\Services\AccountMfa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AccountSecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_sessions_without_password_marker_must_sign_in_again(): void
    {
        $user=User::factory()->create(['is_active'=>true]);
        $this->actingAs($user)->withSession(['browser_password.'.$user->id=>null])->get('/learn/account')->assertRedirect();
        $this->assertGuest('web');
        $this->post('/learn/login',['email'=>$user->email,'password'=>'password'])->assertRedirect();
        $this->get('/learn/account')->assertOk();
    }

    public function test_recovery_failure_has_the_same_public_response_as_unknown_email(): void
    {
        $broker=\Mockery::mock(\Illuminate\Contracts\Auth\PasswordBroker::class);
        $broker->shouldReceive('sendResetLink')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $broker->shouldReceive('sendResetLink')->once()->andReturn(\Illuminate\Support\Facades\Password::INVALID_USER);
        \Illuminate\Support\Facades\Password::shouldReceive('broker')->twice()->andReturn($broker);
        $failure=$this->postJson('/api/auth/forgot-password',['email'=>'known@example.test'])->assertOk();
        $unknown=$this->postJson('/api/auth/forgot-password',['email'=>'unknown@example.test'])->assertOk();
        $this->assertSame($failure->json(),$unknown->json());
    }

    public function test_web_password_change_and_reset_revoke_mobile_tokens(): void
    {
        $user=User::factory()->create(['is_active'=>true]);
        $user->createToken('phone');
        $this->actingAs($user)->put('/learn/settings/password',['current_password'=>'password','password'=>'changed-password-123','password_confirmation'=>'changed-password-123'])->assertRedirect();
        $this->assertSame(0,$user->tokens()->count());
        $user->createToken('phone-after-change');
        $token=\Illuminate\Support\Facades\Password::createToken($user);
        $this->post('/learn/reset-password',['email'=>$user->email,'token'=>$token,'password'=>'reset-password-123','password_confirmation'=>'reset-password-123'])->assertRedirect(route('learn.login'));
        $this->assertSame(0,$user->tokens()->count());
    }

    public function test_api_password_change_invalidates_existing_browser_password_marker(): void
    {
        $user=User::factory()->create(['is_active'=>true,'remember_token'=>'previous-cookie']);
        $oldHash=$user->password;
        $token=$user->createToken('phone')->plainTextToken;
        $other=$user->createToken('other');
        $this->withToken($token)->putJson('/api/auth/password',['current_password'=>'password','new_password'=>'new-password-123','new_password_confirmation'=>'new-password-123'])->assertOk();
        $this->assertNotSame('previous-cookie',$user->fresh()->remember_token);
        $this->assertDatabaseMissing('personal_access_tokens',['id'=>$other->accessToken->id]);
        $this->actingAs($user->fresh(),'web')->withSession(['browser_password.'.$user->id=>$oldHash])->get('/learn/account')->assertRedirect();
        $this->assertGuest('web');
    }

    public function test_public_questions_do_not_leak_solutions(): void
    {
        Question::create(['question'=>'Safe action?','option1'=>'Stop','option2'=>'Rush','option3'=>'Ignore','option4'=>'Speed','correctOption'=>'A','explanation'=>'Secret explanation','status'=>'Published','category'=>'General','difficulty'=>'Easy']);
        $this->getJson('/api/question')->assertOk()->assertJsonPath('data.questions.0.option1','Stop')->assertJsonMissingPath('data.questions.0.correctOption')->assertJsonMissingPath('data.questions.0.explanation');
    }

    public function test_stale_mfa_enrollment_cannot_overwrite_existing_secret(): void
    {
        $user=User::factory()->create();$stale=User::findOrFail($user->id);
        $otp=new Google2FA;$first=$otp->generateSecretKey();$second=$otp->generateSecretKey();
        $service=app(AccountMfa::class);
        $codes=$service->enable($user,$first,$otp->getCurrentOtp($first));
        try {
            $service->enable($stale,$second,$otp->getCurrentOtp($second));
            $this->fail('Enrollment must not overwrite an existing secret.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(409,$e->getStatusCode());
        }
        $this->assertSame($first,$user->fresh()->mfa_secret);
        $this->assertTrue($service->disable($stale,$codes[0]));
        $this->assertFalse($service->disable($user,$codes[0]));
        $this->assertNull($user->fresh()->mfa_secret);
    }
}
