<?php
namespace Tests\Feature;

use App\Models\{DrivingLesson, DrivingSchool, LessonVehicle, SchoolMembership, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class LessonBookingHandoffTest extends TestCase
{
    use RefreshDatabase;

    private function school(): DrivingSchool
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $school = DrivingSchool::create(['name' => 'Bagmati Driving Center', 'status' => 'Approved']);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $teacher->id, 'role' => 'Instructor']);
        $car = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $teacher->id,
            'name' => 'Swift', 'registration' => 'TEST-'.$teacher->id, 'transmission' => 'Automatic']);
        DrivingLesson::create(['lesson_vehicle_id' => $car->id,
            'starts_at' => now()->addDays(2)->setTime(9, 0), 'ends_at' => now()->addDays(2)->setTime(10, 0),
            'price_npr' => 1000, 'meeting_point' => 'Bagmati Driving Center, Dyabu Marg, Kathmandu', 'cancelled' => false]);
        return $school;
    }

    private function lessonsUrl(DrivingSchool $school): string
    {
        return route('learn.lessons', ['school' => $school->id, 'transmission' => 'Automatic']);
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

    public function test_guest_sign_in_returns_to_filtered_lessons_page(): void
    {
        $school = $this->school();
        $learner = User::factory()->create(['is_active' => true]);
        $lessonsUrl = $this->lessonsUrl($school);

        $this->get($lessonsUrl)->assertOk()->assertSee('Sign in to book')->assertSee('redirect=');
        $this->get(route('learn.login', ['redirect' => $lessonsUrl]))->assertOk();
        $this->post('/learn/login', ['email' => $learner->email, 'password' => 'password'])
            ->assertRedirect($lessonsUrl);
        $this->get($lessonsUrl)->assertOk();
    }

    public function test_off_site_redirect_is_not_honored(): void
    {
        $learner = User::factory()->create(['is_active' => true]);
        foreach (['https://evil.example/x', 'https://learn-to-drive.onrender.com/learn/driving-lessons', url('/learn/account')] as $redirect) {
            $this->get(route('learn.login', ['redirect' => $redirect]))->assertOk();
            $this->post('/learn/login', ['email' => $learner->email, 'password' => 'password'])
                ->assertRedirect(route('learn.account'));
            $this->post('/learn/logout');
        }
    }

    public function test_register_after_sign_in_link_returns_to_lessons_page(): void
    {
        $school = $this->school();
        $lessonsUrl = $this->lessonsUrl($school);

        // The login page's "Create an account" link is a plain link: the
        // intended URL survives in the session, not in the URL.
        $this->get(route('learn.login', ['redirect' => $lessonsUrl]))->assertOk();
        $this->get(route('learn.register'))->assertOk();
        $this->post(route('learn.register'), ['name' => 'New Learner', 'email' => 'new-learner@example.test',
            'phoneNumber' => '9800000000', 'password' => 'password-123', 'password_confirmation' => 'password-123',
            'terms' => '1', 'age' => '1'])->assertRedirect($lessonsUrl);
    }

    public function test_mfa_challenge_returns_to_lessons_page(): void
    {
        $school = $this->school();
        $lessonsUrl = $this->lessonsUrl($school);
        $user = User::factory()->create(['is_active' => true]);
        $secret = (new Google2FA)->generateSecretKey();
        $user->forceFill(['mfa_secret' => $secret])->save();

        $this->get(route('learn.login', ['redirect' => $lessonsUrl]))->assertOk();
        $this->post('/learn/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('learn.mfa.challenge'));
        $this->post(route('learn.mfa.verify'), ['code' => (new Google2FA)->getCurrentOtp($secret)])
            ->assertRedirect($lessonsUrl);
    }

    public function test_google_sign_in_returns_to_lessons_page(): void
    {
        $school = $this->school();
        $lessonsUrl = $this->lessonsUrl($school);
        $user = User::factory()->create(['is_active' => true]);
        $user->forceFill(['google_id' => 'google-linked'])->save();

        $this->google('google-linked', $user->email);
        $this->withSession(['url.intended' => $lessonsUrl]);
        $this->get(route('learn.google.callback'))->assertRedirect($lessonsUrl);
    }

    public function test_google_new_account_finish_returns_to_lessons_page(): void
    {
        $school = $this->school();
        $lessonsUrl = $this->lessonsUrl($school);

        $this->google('google-new', 'google-new@example.test');
        $this->withSession(['url.intended' => $lessonsUrl]);
        $this->get(route('learn.google.callback'))->assertRedirect(route('learn.google.finish'));
        $this->post(route('learn.google.finish'), ['name' => 'Learner', 'phoneNumber' => '9800000000',
            'password' => 'password-123', 'password_confirmation' => 'password-123', 'terms' => '1', 'age' => '1'])
            ->assertRedirect($lessonsUrl);
    }
}
