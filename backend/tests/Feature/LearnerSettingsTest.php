<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LearnerSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_require_an_active_account(): void
    {
        $this->get('/learn/settings')->assertRedirect(route('learn.login'));
        $this->patch('/learn/settings/profile',[])->assertRedirect(route('learn.login'));
        $this->actingAs(User::factory()->create(['is_active'=>false]))->get('/learn/settings')->assertRedirect(route('learn.login'));
    }

    public function test_profile_is_owned_and_verification_resets_when_contact_details_change(): void
    {
        $user=User::factory()->create(['is_active'=>true,'phone_verified_at'=>now()]);
        $other=User::factory()->create();
        $this->actingAs($user)->get('/learn/settings')->assertOk()->assertSee('Save full name');
        $data=['name'=>'New name','email'=>'updated@example.test','phoneNumber'=>'9800000000','current_password'=>'password','role'=>'Admin','id'=>$other->id];
        $this->patch('/learn/settings/profile',$data)->assertRedirect(route('learn.settings'));
        $this->assertSame('User',$user->fresh()->role);
        $this->assertSame('updated@example.test',$user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertSame($other->email,$other->fresh()->email);
    }

    public function test_email_change_requires_password_and_unique_email(): void
    {
        $user=User::factory()->create(['is_active'=>true]);
        $other=User::factory()->create();
        $this->actingAs($user);
        $data=['name'=>$user->name,'email'=>'new@example.test','phoneNumber'=>$user->phoneNumber];
        $this->patch('/learn/settings/profile',$data)->assertSessionHasErrors('current_password');
        $data['current_password']='password';$data['email']=$other->email;
        $this->patch('/learn/settings/profile',$data)->assertSessionHasErrors('email');
        $this->assertSame($user->email,$user->fresh()->email);
    }

    public function test_password_change_checks_current_password_and_confirmation(): void
    {
        $user=User::factory()->create(['is_active'=>true]);$this->actingAs($user);
        $data=['current_password'=>'wrong','password'=>'new-password-123','password_confirmation'=>'new-password-123'];
        $this->put('/learn/settings/password',$data)->assertSessionHasErrors('current_password');
        $this->assertArrayNotHasKey('current_password',session()->getOldInput());
        $data['current_password']='password';$data['password_confirmation']='mismatch';
        $this->put('/learn/settings/password',$data)->assertSessionHasErrors('password');
        $data['password_confirmation']=$data['password'];
        $this->put('/learn/settings/password',$data)->assertRedirect(route('learn.settings'));
        $this->assertTrue(Hash::check($data['password'],$user->fresh()->password));
        $this->assertFalse(Hash::check('password',$user->fresh()->password));
    }
}
