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

    public function test_course_and_session_subtitles(): void
    {
        $this->assertSame('Sign in to request this session.',
            LearnerIntendedRedirect::subtitleFor(url('/learn/private-lessons')));
        $this->assertSame('Sign in to request this session.',
            LearnerIntendedRedirect::subtitleFor(url('/learn/private-lessons').'?school=2&instructor=3'));
        $this->assertSame('Sign in to book this course.',
            LearnerIntendedRedirect::subtitleFor(url('/learn/driving-lessons').'?lesson=5'));
        $this->assertSame('Sign in to see your bookings.',
            LearnerIntendedRedirect::subtitleFor(url('/learn/driving-lessons/bookings')));
        $this->assertNull(LearnerIntendedRedirect::subtitleFor(url('/learn/driving-lessons')));
        $this->assertNull(LearnerIntendedRedirect::subtitleFor(url('/learn/driving-lessons').'?school=2'));
        // The lesson flag must come from the query string, not from a
        // substring anywhere in the URL.
        $this->assertNull(LearnerIntendedRedirect::subtitleFor(url('/learn/driving-lessons').'/lesson/5'));

        // End to end: the bookings page bounces a guest to sign-in with
        // the bookings subtitle, and a plain course list keeps the
        // generic line.
        $this->get(route('learn.lessons.bookings'))->assertRedirect(route('learn.login'));
        $this->get(route('learn.login'))->assertOk()->assertSee('Sign in to see your bookings.');
        $this->get(route('learn.login', ['redirect' => url('/learn/private-lessons')]))->assertOk()
            ->assertSee('Sign in to request this session.');
        $this->get(route('learn.login', ['redirect' => url('/learn/driving-lessons').'?lesson=5']))->assertOk()
            ->assertSee('Sign in to book this course.');
        $this->get(route('learn.login', ['redirect' => url('/learn/driving-lessons')]))->assertOk()
            ->assertSee('Sign in to continue practising.');
    }
}
