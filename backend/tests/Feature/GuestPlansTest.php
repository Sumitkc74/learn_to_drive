<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
class GuestPlansTest extends TestCase {
    use RefreshDatabase;
    public function test_guest_sees_plan_and_upgrade_line(): void {
        config(['services.google.client_id'=>null]);
        $this->get('/learn/premium')->assertOk()->assertSee('Start free. Grow with Premium.')->assertSee('REGISTERED (FREE)')->assertSee('PREMIUM (PAID)')->assertSee('Already available to guests')->assertSee('Create an account')->assertSee('to upgrade to Premium.')->assertSee('/learn/register?premium=1',false)->assertSee('Sign in with Google')->assertSee('coming soon')->assertSee('id="auth-signup"',false)->assertSee('id="auth-signin"',false)->assertDontSee('id="premium-upgrade"',false)->assertDontSee('data-open-upgrade-dialog',false)->assertDontSee('Upgrade to Premium')->assertDontSee('Online checkout is not available yet. Contact the admin team about Premium access.')->assertDontSee('Sign in to request Premium')->assertDontSee('priority support')->assertDontSee('unlimited access');
        $this->get('/learn/login')->assertOk()->assertSee('Sign in with Google')->assertDontSee('id="auth-signin"',false);
        config(['services.google.client_id'=>'test','services.google.client_secret'=>'test','services.google.redirect'=>'http://localhost/callback']);
        $this->get('/learn/register')->assertOk()->assertSee('href="'.route('learn.google').'"',false)->assertDontSee('coming soon');
    }
}
