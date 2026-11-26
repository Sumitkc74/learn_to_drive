<?php
namespace Tests\Feature;

use App\Models\{Question, SupportTicket, User};
use App\Support\LearnerIntendedRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportReportSignInTest extends TestCase
{
    use RefreshDatabase;

    private function learner(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    private function question(): Question
    {
        return Question::create(['question' => 'What does a red light mean?', 'option1' => 'Stop', 'option2' => 'Go',
            'option3' => 'Slow', 'option4' => 'Turn', 'correctOption' => 'A', 'explanation' => 'Red means stop.',
            'status' => 'Published', 'category' => 'General', 'difficulty' => 'Easy']);
    }

    public function test_guest_support_visit_returns_after_sign_in(): void
    {
        $learner = $this->learner();
        $this->get(route('learn.support'))->assertRedirect(route('learn.login'));
        $this->get(route('learn.login'))->assertOk()
            ->assertSee('Sign in to message the admin team and track their replies.');
        $this->post(route('learn.login'), ['email' => $learner->email, 'password' => 'password'])
            ->assertRedirect(route('learn.support'));
        $this->get(route('learn.support'))->assertOk();
    }

    public function test_guest_ticket_detail_visit_returns_after_sign_in(): void
    {
        $learner = $this->learner();
        $ticket = SupportTicket::create(['type' => 'Complaint', 'subject' => 'Cannot load a page',
            'message' => 'The library page is blank.', 'user_id' => $learner->id, 'status' => 'New']);
        $url = route('learn.support.show', $ticket);
        $this->get($url)->assertRedirect(route('learn.login'));
        $this->get(route('learn.login'))->assertOk()
            ->assertSee('Sign in to message the admin team and track their replies.');
        $this->post(route('learn.login'), ['email' => $learner->email, 'password' => 'password'])
            ->assertRedirect($url);
        $this->get($url)->assertOk()->assertSee('Cannot load a page');
    }

    public function test_guest_report_visit_returns_after_sign_in(): void
    {
        $learner = $this->learner();
        $url = route('learn.report', ['type' => 'question', 'id' => $this->question()->id]);
        $this->get($url)->assertRedirect(route('learn.login'));
        $this->get(route('learn.login'))->assertOk()
            ->assertSee('Sign in to report this content.');
        $this->post(route('learn.login'), ['email' => $learner->email, 'password' => 'password'])
            ->assertRedirect($url);
        $this->get($url)->assertOk();
    }

    public function test_login_subtitle_is_generic_without_a_destination(): void
    {
        $this->get(route('learn.login'))->assertOk()
            ->assertSee('Sign in to continue practising.')
            ->assertDontSee('message the admin team')
            ->assertDontSee('report this content');
    }

    public function test_admin_support_url_is_not_an_allowlisted_return(): void
    {
        $this->assertFalse(LearnerIntendedRedirect::allowed(url('/admin/support')));
        $this->assertTrue(LearnerIntendedRedirect::allowed(url('/learn/support')));
        $this->assertTrue(LearnerIntendedRedirect::allowed(url('/learn/report/question/1')));
        $this->assertSame('Sign in to message the admin team and track their replies.',
            LearnerIntendedRedirect::subtitleFor(url('/learn/support')));
        $this->assertSame('Sign in to report this content.',
            LearnerIntendedRedirect::subtitleFor(url('/learn/report/question/1')));
        $this->assertNull(LearnerIntendedRedirect::subtitleFor(url('/learn/driving-lessons')));
    }
}
