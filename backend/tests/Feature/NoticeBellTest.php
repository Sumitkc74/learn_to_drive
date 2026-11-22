<?php
namespace Tests\Feature;

use App\Models\{Notice, NoticeRead, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeBellTest extends TestCase
{
    use RefreshDatabase;

    private function notice(array $overrides = []): Notice
    {
        return Notice::create(array_merge([
            'title' => 'Exam schedule update',
            'description' => 'The written exam schedule has changed for next month.',
            'nepaliTitle' => 'परीक्षा सूचना',
            'nepaliDescription' => 'विवरण',
            'status' => 'Published',
        ], $overrides));
    }

    private function learner(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    public function test_bell_shows_unread_badge_for_signed_in_user(): void
    {
        $notice = $this->notice();
        $this->actingAs($this->learner())->get(route('learn.account'))->assertOk()
            ->assertSee('notice-badge')
            ->assertSee('Exam schedule update')
            ->assertSee(route('learn.detail', ['notice', $notice->id]));
    }

    public function test_dropdown_lists_latest_five_notices(): void
    {
        for ($i = 1; $i <= 6; $i++) $this->notice(['title' => 'Notice ' . $i]);
        $this->actingAs($this->learner())->get(route('learn.account'))->assertOk()
            ->assertSee('Notice 6')->assertSee('Notice 2')->assertDontSee('Notice 1')
            ->assertSee('View all notices');
    }

    public function test_opening_a_notice_marks_it_read_and_badge_drops(): void
    {
        $notice = $this->notice();
        $user = $this->learner();
        $this->actingAs($user)->get(route('learn.detail', ['notice', $notice->id]))->assertOk();
        $this->assertDatabaseHas('notice_reads', ['user_id' => $user->id, 'notice_id' => $notice->id]);
        $this->actingAs($user)->get(route('learn.account'))->assertOk()->assertDontSee('notice-badge');
    }

    public function test_mark_all_as_read_clears_badge(): void
    {
        $this->notice();
        $this->notice(['title' => 'Second notice']);
        $user = $this->learner();
        $this->actingAs($user)->post(route('learn.notices.read-all'))
            ->assertRedirect()->assertSessionHas('success', 'Notifications marked as read.');
        $this->assertDatabaseCount('notice_reads', 2);
        $this->actingAs($user)->get(route('learn.account'))->assertOk()->assertDontSee('notice-badge');
    }

    public function test_guest_sees_bell_without_badge(): void
    {
        $this->notice();
        $this->get(route('learn.home'))->assertOk()
            ->assertSee('notice-bell')->assertSee('Exam schedule update')
            ->assertDontSee('notice-badge');
    }

    public function test_read_state_is_per_user(): void
    {
        $notice = $this->notice();
        $first = $this->learner();
        $second = $this->learner();
        $this->actingAs($first)->get(route('learn.detail', ['notice', $notice->id]))->assertOk();
        $this->actingAs($second)->get(route('learn.account'))->assertOk()->assertSee('notice-badge');
        $this->actingAs($first)->get(route('learn.account'))->assertOk()->assertDontSee('notice-badge');
    }

    public function test_draft_and_expired_notices_are_not_in_the_bell(): void
    {
        $this->notice(['title' => 'Draft notice', 'status' => 'Draft']);
        $this->notice(['title' => 'Expired notice', 'expires_at' => now()->subDay()]);
        $this->get(route('learn.home'))->assertOk()
            ->assertDontSee('Draft notice')->assertDontSee('Expired notice')
            ->assertSee('No notices yet.');
    }

    public function test_mark_all_as_read_requires_sign_in(): void
    {
        $this->post(route('learn.notices.read-all'))->assertRedirect(route('learn.login'));
    }
}
