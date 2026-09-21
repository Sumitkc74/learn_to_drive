<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginDestinationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_using_learner_login_reaches_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role'=>'Admin','is_active'=>true]);
        $this->post('/learn/login', ['email'=>$admin->email,'password'=>'password'])
            ->assertRedirect(route('adminDashboard'));
        $this->get('/learn/login')->assertRedirect(route('adminDashboard'));
        $this->get('/learn/register')->assertRedirect(route('adminDashboard'));
    }

    public function test_admin_login_ignores_saved_learner_destination(): void
    {
        $admin = User::factory()->create(['role'=>'Admin','is_active'=>true]);
        $this->withSession(['url.intended'=>route('learn.account')])
            ->post('/login', ['email'=>$admin->email,'password'=>'password'])
            ->assertRedirect(route('adminDashboard'));
    }

    public function test_signed_in_learner_is_not_redirected_to_admin(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'User','is_active'=>true]))
            ->get('/login')->assertRedirect(route('learn.account'));
    }
}
