<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AdminFlashMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_pages_render_each_global_message_once(): void
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        foreach (['allUser', 'allQuestion', 'allNotice', 'allTrafficSign', 'allVisionTest', 'allExamPaper', 'allExamInformation', 'allTutorial', 'questionTrash'] as $route) {
            $response = $this->withSession([
                'success' => 'Unique success message',
                'errors' => (new ViewErrorBag)->put('default', new MessageBag(['record' => 'Unique failure message'])),
                'import_summary' => ['added' => 1, 'skipped' => 0, 'duplicate' => 0, 'invalid' => 0],
            ])->get(route($route))->assertOk();
            $html = $response->getContent();
            $this->assertSame(1, substr_count($html, 'Unique success message'), $route);
            $this->assertSame(1, substr_count($html, 'Unique failure message'), $route);
            $this->assertSame(1, substr_count($html, 'Import summary:'), $route);
            $this->assertSame(1, substr_count($html, 'id="admin-success-alert"'), $route);
            $this->assertSame(1, substr_count($html, 'id="admin-error-alert"'), $route);
        }
    }

    public function test_adding_a_user_shows_one_success_message_after_redirect(): void
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        $this->post(route('insertUser'), [
            'name' => 'New learner', 'email' => 'new-learner@example.com',
            'phoneNumber' => '9812345678', 'role' => 'User',
            'password' => 'secure-password', 'password_confirmation' => 'secure-password',
        ])->assertRedirect(route('allUser'));
        $message = session('success');
        $this->assertNotEmpty($message);
        $response = $this->get(route('allUser'))->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), e($message)));
        $this->get(route('allUser'))->assertDontSee($message);
    }
}
