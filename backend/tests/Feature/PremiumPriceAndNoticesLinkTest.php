<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumPriceAndNoticesLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_notices_empty_state_links_to_official_dotm_notices(): void
    {
        $this->get(route('learn.library', 'notice'))
            ->assertOk()
            ->assertSee('No resources found. Try another search or visit again soon.')
            ->assertSee('See the latest notices on the Department of Transport Management official website')
            ->assertSee('href="https://dotm.gov.np/"', false);
    }

    public function test_notices_empty_state_link_uses_the_importer_config_url(): void
    {
        config(['services.dotm.notices_url' => 'https://www.dotm.gov.np/']);

        $this->get(route('learn.library', 'notice'))
            ->assertOk()
            ->assertSee('href="https://www.dotm.gov.np/"', false);
    }

    public function test_notices_empty_state_link_does_not_show_on_other_library_types(): void
    {
        $this->get(route('learn.library', 'tutorial'))
            ->assertOk()
            ->assertDontSee('Department of Transport Management official website');
    }

    public function test_guest_premium_page_shows_price(): void
    {
        $this->get(route('learn.premium'))
            ->assertOk()
            ->assertSee('PREMIUM (PAID)')
            ->assertSee('Rs 500, one-time, lifetime')
            ->assertSee('Checkout will be available once configured.');
    }

    public function test_signed_in_premium_page_shows_price(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('learn.premium'))
            ->assertOk()
            ->assertSee('Rs 500')
            ->assertSee('one-time payment for lifetime access')
            ->assertSee('Online checkout is not available yet.');
    }
}
