<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
class AdminCreatedPremiumLoginTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_created_premium_account_can_sign_in_and_access_premium():void
    {
        $admin=User::factory()->create(['role'=>'Admin','is_active'=>true]);
        $this->actingAs($admin)->post(route('insertUser'),['name'=>'Premium learner','email'=>'premium@example.test','phoneNumber'=>'9800000000','role'=>'PremiumUser','password'=>'test-password-123','password_confirmation'=>'test-password-123'])->assertRedirect('/admin/users');
        $user=User::where('email','premium@example.test')->firstOrFail();
        $this->assertTrue($user->is_active);$this->assertTrue(Hash::check('test-password-123',$user->password));
        $this->post('/logout');
        $this->post('/learn/login',['email'=>$user->email,'password'=>'test-password-123'])->assertRedirect(route('learn.account'));
        $this->get(route('learn.account'))->assertOk();
        $this->get(route('learn.premium.modules'))->assertOk();
        $this->post('/learn/logout');
        $this->post('/login',['email'=>$user->email,'password'=>'test-password-123'])->assertRedirect(route('learn.login'))->assertSessionHas('error');
        $this->assertGuest();
    }
}
