<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    private function registration(string $email): array
    {
        return ['name' => 'New Learner', 'email' => $email, 'phoneNumber' => '9800000001', 'password' => 'Password123', 'password_confirmation' => 'Password123', 'terms' => '1'];
    }

    private function google(string $id, string $email): void
    {
        config(['services.google.client_id' => 'test', 'services.google.client_secret' => 'test', 'services.google.redirect' => 'http://localhost/learn/auth/google/callback']);
        $profile = (new \Laravel\Socialite\Two\User)->setRaw(['email_verified' => true])->map(['id' => $id, 'email' => $email, 'name' => 'Google learner']);
        $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($profile);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->withSession(['google_flow' => ['expires' => time() + 600, 'link' => null]]);
    }

    private function googleFinishPayload(): array
    {
        return ['name' => 'Learner', 'phoneNumber' => '9800000000', 'password' => 'password-123', 'password_confirmation' => 'password-123', 'terms' => '1'];
    }

    public function test_privacy_page_loads_for_a_guest_with_the_key_lines(): void
    {
        $this->get(route('learn.privacy'))->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('What we collect')
            ->assertSee('Who else sees your data')
            ->assertSee('at least 16 years old')
            ->assertSee('Category A')
            ->assertSee('Open a support ticket')
            ->assertSee('href="' . route('learn.support') . '"', false)
            ->assertSee('href="' . route('learn.cookies') . '"', false)
            ->assertSee('Last updated: November 2026.');
    }

    public function test_cookies_page_loads_for_a_guest_and_lists_only_essential_cookies(): void
    {
        $this->get(route('learn.cookies'))->assertOk()
            ->assertSee('Cookie Policy')
            ->assertSee('learn_to_drive_session')
            ->assertSee('XSRF-TOKEN')
            ->assertSee('learner_locale')
            ->assertSee('essential for the site to work')
            ->assertSee('We do not use analytics or advertising trackers')
            ->assertSee('we do not show a cookie consent banner')
            ->assertSee('href="' . route('learn.privacy') . '"', false)
            ->assertSee('Last updated: November 2026.');
    }

    public function test_terms_page_links_the_privacy_policy_and_states_the_minimum_age(): void
    {
        $this->get(route('learn.terms'))->assertOk()
            ->assertSee('href="' . route('learn.privacy') . '"', false)
            ->assertSee('Read the Privacy Policy')
            ->assertSee('at least 16 years old')
            ->assertSee('Online checkout is not available yet');
    }

    public function test_footer_links_privacy_and_cookies_for_guests_and_signed_in_users(): void
    {
        $this->get(route('learn.home'))->assertOk()
            ->assertSee('href="' . route('learn.privacy') . '"', false)
            ->assertSee('href="' . route('learn.cookies') . '"', false);
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->get(route('learn.account'))->assertOk()
            ->assertSee('href="' . route('learn.privacy') . '"', false)
            ->assertSee('href="' . route('learn.cookies') . '"', false);
    }

    public function test_register_form_has_age_checkbox_and_privacy_link(): void
    {
        $this->get(route('learn.register'))->assertOk()
            ->assertSee('name="age"', false)
            ->assertSee('I confirm I am at least 16 years old')
            ->assertSee('href="' . route('learn.privacy') . '"', false)
            ->assertSee('I agree to the');
    }

    public function test_registration_without_age_fails_and_creates_no_user(): void
    {
        $this->post(route('learn.register'), $this->registration('no-age@example.com'))
            ->assertSessionHasErrors('age');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'no-age@example.com']);
    }

    public function test_registration_with_age_and_terms_succeeds(): void
    {
        $this->post(route('learn.register'), $this->registration('with-age@example.com') + ['age' => '1'])
            ->assertRedirect(route('learn.account'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'with-age@example.com']);
    }

    public function test_google_finish_without_age_fails_and_creates_no_user(): void
    {
        $this->google('google-no-age', 'google-no-age@example.test');
        $this->get(route('learn.google.callback'))->assertRedirect(route('learn.google.finish'));
        $this->post(route('learn.google.finish'), $this->googleFinishPayload())
            ->assertSessionHasErrors('age');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'google-no-age@example.test']);
    }

    public function test_google_finish_with_age_and_terms_succeeds(): void
    {
        $this->google('google-with-age', 'google-with-age@example.test');
        $this->get(route('learn.google.callback'))->assertRedirect(route('learn.google.finish'));
        $this->post(route('learn.google.finish'), $this->googleFinishPayload() + ['age' => '1'])
            ->assertRedirect(route('learn.account'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'google-with-age@example.test']);
    }

    public function test_google_finish_form_has_age_checkbox_and_privacy_link(): void
    {
        $this->google('google-form-age', 'google-form-age@example.test');
        $this->get(route('learn.google.callback'))->assertRedirect(route('learn.google.finish'));
        $this->get(route('learn.google.finish'))->assertOk()
            ->assertSee('name="age"', false)
            ->assertSee('I confirm I am at least 16 years old')
            ->assertSee('href="' . route('learn.privacy') . '"', false);
    }

    public function test_premium_page_shows_not_live_and_refund_lines_to_guests(): void
    {
        $this->get(route('learn.premium'))->assertOk()
            ->assertSee('Online checkout is not available yet')
            ->assertSee('no money is taken')
            ->assertSee('Rs 500, a one-time payment for lifetime access')
            ->assertSee('no automatic renewal')
            ->assertSee('we will look into a refund');
    }

    public function test_settings_has_a_your_data_section_linking_privacy_and_support(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->get(route('learn.settings'))->assertOk()
            ->assertSee('Your data')
            ->assertSee('href="' . route('learn.privacy') . '"', false)
            ->assertSee('href="' . route('learn.support') . '"', false)
            ->assertSee('corrected or deleted');
    }
}
