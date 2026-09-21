<?php
namespace Tests\Feature;
use App\Models\User;
use App\Notifications\{LearnerVerifyEmail,LearnerResetPassword};
use Illuminate\Support\Facades\{Notification,URL,Hash};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class LearnerEmailSecurityTest extends TestCase
{
    use RefreshDatabase;
    public function test_fields_save_independently_and_verification_is_signed_and_owned(): void
    {
        Notification::fake();
        $user=User::factory()->unverified()->create(['is_active'=>true]);
        $this->actingAs($user)->patch('/learn/settings/detail/name',['name'=>'Updated','role'=>'Admin'])->assertRedirect(route('learn.settings'));
        $this->assertSame('Updated',$user->fresh()->name);$this->assertSame('User',$user->fresh()->role);
        $this->post('/learn/verify-email')->assertRedirect();
        Notification::assertSentTo($user,LearnerVerifyEmail::class);
        $url=URL::temporarySignedRoute('learn.verification.verify',now()->addHour(),['id'=>$user->id,'hash'=>sha1($user->email)]);
        $this->get($url.'&tampered=1')->assertForbidden();
        $this->actingAs(User::factory()->create(['is_active'=>true]))->get($url)->assertForbidden();
        $this->actingAs($user)->get($url)->assertRedirect(route('learn.settings'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->patch('/learn/settings/detail/email',['email'=>'new@example.test','current_password'=>'password'])->assertRedirect();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->get($url)->assertForbidden();
    }
    public function test_reset_email_and_single_use_token_work_for_unverified_users(): void
    {
        Notification::fake();
        $user=User::factory()->unverified()->create(['is_active'=>true]);
        $this->get('/learn/forgot-password')->assertOk();
        $this->post('/learn/forgot-password',['email'=>$user->email])->assertSessionHas('success');
        $token=null;
        Notification::assertSentTo($user,LearnerResetPassword::class,function($mail) use (&$token,$user){$token=$mail->token;$this->assertStringContainsString('/learn/reset-password/',$mail->toMail($user)->actionUrl);return true;});
        $data=['email'=>$user->email,'token'=>$token,'password'=>'new-password-123','password_confirmation'=>'new-password-123'];
        $this->get(route('learn.password.reset',['token'=>$token,'email'=>$user->email]))->assertOk();
        $this->post('/learn/reset-password',$data)->assertRedirect(route('learn.login'));
        $this->assertTrue(Hash::check('new-password-123',$user->fresh()->password));
        $this->post('/learn/reset-password',$data)->assertSessionHasErrors('email');
        $this->post('/learn/forgot-password',['email'=>'unknown@example.test'])->assertSessionHas('success');
    }
}
