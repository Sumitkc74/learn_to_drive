<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
class GuestPlansTest extends TestCase {
    use RefreshDatabase;
    public function test_guest_sees_auth_choices_dialogs_and_accurate_plans(): void {
        config(['services.google.client_id'=>null]);
        $this->get('/learn/premium')->assertOk()->assertSee('REGISTERED (FREE)')->assertSee('PREMIUM (PAID)')->assertSee('Already available to guests')->assertSee('Sign in with Google')->assertSee('coming soon')->assertSee('id="auth-signup"',false)->assertSee('id="auth-signin"',false)->assertDontSee('priority support')->assertDontSee('unlimited access');
        $this->get('/learn/login')->assertOk()->assertSee('Sign in with Google')->assertDontSee('id="auth-signin"',false);
        config(['services.google.client_id'=>'test','services.google.client_secret'=>'test','services.google.redirect'=>'http://localhost/callback']);
        $this->get('/learn/register')->assertOk()->assertSee('href="'.route('learn.google').'"',false)->assertDontSee('coming soon');
    }
}
