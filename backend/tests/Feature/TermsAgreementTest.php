<?php
namespace Tests\Feature;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
class TermsAgreementTest extends TestCase {
    use RefreshDatabase;
    private function google(string $id,string $email): void {
        config(['services.google.client_id'=>'test','services.google.client_secret'=>'test','services.google.redirect'=>'http://localhost/learn/auth/google/callback']);
        $profile=(new \Laravel\Socialite\Two\User)->setRaw(['email_verified'=>true])->map(['id'=>$id,'email'=>$email,'name'=>'Google learner']);
        $provider=\Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($profile);Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->withSession(['google_flow'=>['expires'=>time()+600,'link'=>null]]);
    }
    private function googleFinishPayload(): array {
        return ['name'=>'Learner','phoneNumber'=>'9800000000','password'=>'password-123','password_confirmation'=>'password-123','age'=>'1'];
    }
    private function registration(string $email): array {
        return ['name'=>'New Learner','email'=>$email,'phoneNumber'=>'9800000001','password'=>'Password123','password_confirmation'=>'Password123','age'=>'1'];
    }
    public function test_registration_without_terms_fails_and_creates_no_user(): void {
        $this->post(route('learn.register'),$this->registration('no-terms@example.com'))
            ->assertSessionHasErrors('terms');
        $this->assertGuest();
        $this->assertDatabaseMissing('users',['email'=>'no-terms@example.com']);
    }
    public function test_registration_with_terms_succeeds(): void {
        $this->post(route('learn.register'),$this->registration('with-terms@example.com')+['terms'=>'1'])
            ->assertRedirect(route('learn.account'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users',['email'=>'with-terms@example.com']);
    }
    public function test_terms_page_loads_for_a_guest(): void {
        $this->get(route('learn.terms'))->assertOk()
            ->assertSee('Terms and Conditions')
            ->assertSee('What this service is')
            ->assertSee('A study aid, not official material')
            ->assertSee('Premium')
            ->assertSee('Last updated: November 2026.');
    }
    public function test_register_form_has_terms_checkbox_and_link_but_sign_in_does_not(): void {
        $this->get(route('learn.register'))->assertOk()
            ->assertSee('name="terms"',false)
            ->assertSee('I agree to the')
            ->assertSee('href="'.route('learn.terms').'"',false)
            ->assertSee('target="_blank"',false);
        $this->get(route('learn.login'))->assertOk()
            ->assertDontSee('name="terms"',false)
            ->assertDontSee('I agree to the');
    }
    public function test_footer_links_the_terms_page_for_guests(): void {
        $this->get(route('learn.home'))->assertOk()
            ->assertSee('href="'.route('learn.terms').'"',false)
            ->assertSee('Terms and Conditions');
    }
    public function test_google_finish_without_terms_fails_and_creates_no_user(): void {
        $this->google('google-no-terms','google-no-terms@example.test');
        $this->get(route('learn.google.callback'))->assertRedirect(route('learn.google.finish'));
        $this->post(route('learn.google.finish'),$this->googleFinishPayload())
            ->assertSessionHasErrors('terms');
        $this->assertGuest();
        $this->assertDatabaseMissing('users',['email'=>'google-no-terms@example.test']);
    }
    public function test_google_finish_with_terms_succeeds(): void {
        $this->google('google-with-terms','google-with-terms@example.test');
        $this->get(route('learn.google.callback'))->assertRedirect(route('learn.google.finish'));
        $this->post(route('learn.google.finish'),$this->googleFinishPayload()+['terms'=>'1'])
            ->assertRedirect(route('learn.account'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users',['email'=>'google-with-terms@example.test']);
    }
    public function test_google_finish_form_has_terms_checkbox_and_link(): void {
        $this->google('google-form','google-form@example.test');
        $this->get(route('learn.google.callback'))->assertRedirect(route('learn.google.finish'));
        $this->get(route('learn.google.finish'))->assertOk()
            ->assertSee('name="terms"',false)
            ->assertSee('I agree to the')
            ->assertSee('href="'.route('learn.terms').'"',false)
            ->assertSee('target="_blank"',false);
    }
}
