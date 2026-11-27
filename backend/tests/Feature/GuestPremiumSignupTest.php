<?php
namespace Tests\Feature;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
class GuestPremiumSignupTest extends TestCase {
    use RefreshDatabase;
    private function gatewaysReady(): void {
        config(['payments.enabled'=>true,'payments.live'=>false,'payments.amount_paisa'=>49900,'payments.days'=>30,'payments.khalti_key'=>'test-key','payments.esewa_code'=>'TEST','payments.esewa_secret'=>'test-secret']);
    }
    private function registration(string $email, bool $premium=false): array {
        $data=['name'=>'New Learner','email'=>$email,'phoneNumber'=>'9800000001','password'=>'Password123','password_confirmation'=>'Password123','terms'=>'1','age'=>'1'];
        if($premium) $data['premium']='1';
        return $data;
    }
    public function test_guest_sees_plan_and_upgrade_line_when_checkout_available(): void {
        $this->gatewaysReady();
        $this->get(route('learn.premium'))->assertOk()
            ->assertSee('Start free. Grow with Premium.')
            ->assertSee('REGISTERED (FREE)')
            ->assertSee('Your learning, saved.')
            ->assertSee('PREMIUM (PAID)')
            ->assertSee('Turn mistakes into understanding.')
            ->assertSee('Already available to guests')
            ->assertSee('Create a free account')
            ->assertSee('Create an account to get Premium')
            ->assertSee('Create an account')
            ->assertSee('to upgrade to Premium.')
            ->assertSee('/learn/register?premium=1',false)
            ->assertSee('499.00')
            ->assertDontSee('data-open-upgrade-dialog',false)
            ->assertDontSee('id="premium-upgrade"',false)
            ->assertDontSee('Upgrade to Premium')
            ->assertDontSee('Online checkout is not available yet. Contact the admin team about Premium access.')
            ->assertDontSee('Sign in to upgrade')
            ->assertDontSee('Sign in to request Premium')
            ->assertDontSee('premium-checkout')
            ->assertDontSee('Pay with')
            ->assertDontSee('Request Premium upgrade')
            ->assertDontSee('You need an account first');
    }
    public function test_guest_sees_plan_and_upgrade_line_when_checkout_unavailable(): void {
        $this->get(route('learn.premium'))->assertOk()
            ->assertSee('Start free. Grow with Premium.')
            ->assertSee('REGISTERED (FREE)')
            ->assertSee('Your learning, saved.')
            ->assertSee('PREMIUM (PAID)')
            ->assertSee('Turn mistakes into understanding.')
            ->assertSee('Already available to guests')
            ->assertSee('Create a free account')
            ->assertSee('Premium — coming soon')
            ->assertSee('href="'.route('learn.register').'" data-auth-open="signup"',false)
            ->assertDontSee('Create an account to get Premium')
            ->assertDontSee('to upgrade to Premium.')
            ->assertDontSee('/learn/register?premium=1',false)
            ->assertDontSee('data-open-upgrade-dialog',false)
            ->assertDontSee('id="premium-upgrade"',false)
            ->assertDontSee('Upgrade to Premium')
            ->assertDontSee('Online checkout is not available yet. Contact the admin team about Premium access.')
            ->assertDontSee('Sign in to upgrade')
            ->assertDontSee('Sign in to request Premium')
            ->assertDontSee('premium-checkout')
            ->assertDontSee('Pay with')
            ->assertDontSee('Request Premium upgrade')
            ->assertDontSee('You need an account first');
    }
    public function test_signed_in_user_keeps_upgrade_card_and_no_guest_cards(): void {
        $this->gatewaysReady();
        $user=User::factory()->create(['is_active'=>true]);
        $this->actingAs($user)->get(route('learn.premium'))->assertOk()
            ->assertSee('Upgrade to Premium')
            ->assertSee('Pay with')
            ->assertDontSee('Your learning, saved.')
            ->assertDontSee('Create an account to get Premium');
    }
    public function test_signed_in_user_sees_only_the_upgrade_card_no_feature_cards(): void {
        $this->gatewaysReady();
        $user=User::factory()->create(['is_active'=>true]);
        $this->actingAs($user)->get(route('learn.premium'))->assertOk()
            ->assertSee('A little extra support.')
            ->assertSee('Upgrade to Premium')
            ->assertSee('Pay with')
            ->assertSee('Students enrolled at an approved driving school get Premium')
            ->assertSee('Regular practice and the learning library remain available without Premium.')
            ->assertDontSee('Your Gemini study coach')
            ->assertDontSee('Personalized practice & revision')
            ->assertDontSee('Open chatbot')
            ->assertDontSee('Build my practice set');
    }
    public function test_premium_user_sees_extend_card_and_badge_without_feature_cards(): void {
        $this->gatewaysReady();
        $user=User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']);
        $this->actingAs($user)->get(route('learn.premium'))->assertOk()
            ->assertSee('Extend Premium')
            ->assertSee('Premium access active')
            ->assertDontSee('Your Gemini study coach')
            ->assertDontSee('Personalized practice & revision')
            ->assertDontSee('Open chatbot')
            ->assertDontSee('Build my practice set');
    }
    public function test_register_page_carries_premium_flag_into_the_form(): void {
        $this->get(route('learn.register', ['premium' => 1]))->assertOk()
            ->assertSee('name="premium" value="1"', false);
    }
    public function test_register_with_premium_flag_lands_on_home_with_offer(): void {
        $this->gatewaysReady();
        $this->post(route('learn.register'),$this->registration('new@example.com',true))
            ->assertRedirect(route('learn.home'));
        $this->assertAuthenticated();
        // Same request chain the modal signup submits: the flag rides in the
        // form, so the landing and the popup are identical either way.
        $this->get(route('learn.home'))->assertOk()
            ->assertSee('id="premium-offer"',false)
            ->assertSee('Your account is ready')
            ->assertSee('View Premium')
            ->assertSee('Not now');
    }
    public function test_register_without_premium_flag_goes_to_account_as_before(): void {
        $this->post(route('learn.register'),$this->registration('plain@example.com'))
            ->assertRedirect(route('learn.account'));
        $this->assertAuthenticated();
        $this->get(route('learn.home'))->assertRedirect(route('learn.account'));
    }
    public function test_home_shows_no_premium_popup_without_the_flag(): void {
        $this->get(route('learn.home'))->assertOk()->assertDontSee('id="premium-offer"',false);
    }
    public function test_sign_in_still_goes_to_account(): void {
        $user=User::factory()->create(['is_active'=>true]);
        $this->post(route('learn.login'),['email'=>$user->email,'password'=>'password'])
            ->assertRedirect(route('learn.account'));
    }
}
