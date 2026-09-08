<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_focuses_on_management_and_quick_actions(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $learner = User::factory()->create(['role' => 'PremiumUser', 'email_verified_at' => now(), 'phone_verified_at' => now()]);
        $question = Question::create(['question' => 'Frequently missed', 'option1' => 'A', 'option2' => 'B', 'option3' => 'C', 'option4' => 'D', 'correctOption' => 'A', 'category' => 'General', 'difficulty' => 'Medium', 'status' => 'Published']);
        UserHistory::create(['user_id' => $learner->id, 'attempted_questions' => json_encode([$question->id]), 'optionA' => '[]', 'optionB' => '[]', 'optionC' => '[]', 'optionD' => '[]', 'correct_options' => json_encode(['A']), 'selected_options' => json_encode(['B'])]);
        $this->actingAs($admin)->get(route('adminDashboard'))
            ->assertOk()
            ->assertSee('Management')
            ->assertSee('Accounts, roles and learning history')
            ->assertSee('Quick Actions')
            ->assertDontSee('Learning Health')
            ->assertDontSee('Recent Admin Activity');
    }

    public function test_analytics_has_its_own_page_and_sidebar_exposes_management_pages(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->get(route('adminAnalytics'))
            ->assertOk()
            ->assertSee('Reports &amp; Insights', false)
            ->assertSee('Total users')
            ->assertSee('Learning Health')
            ->assertSee('Recent Admin Activity')
            ->assertSee('Users')
            ->assertSee('Questions')
            ->assertSee('Vision Tests')
            ->assertSee('Notices')
            ->assertSee('Government Review');
    }

    public function test_legacy_home_route_redirects_admin_to_the_data_backed_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->get('/home')
            ->assertRedirect(route('adminDashboard'));

        $this->followingRedirects()->actingAs($admin)->get('/home')
            ->assertOk()
            ->assertSee('Management');
    }
}
