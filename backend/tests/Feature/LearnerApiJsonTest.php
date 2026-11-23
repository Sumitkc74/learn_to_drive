<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Same latent class as the school-centre API (Nov 20): the /api/learner/*
// group shares controllers with the web learner pages, and those
// controllers render Blade views for requests that do not expect JSON.
// The API group has no session middleware, so the learner layout's
// $errors is undefined and the view throws a 500. ForceJsonResponse on
// the learner group makes every request there expect JSON, so a client
// that forgets the Accept header gets JSON instead of a 500 or a
// redirect to the home page.
class LearnerApiJsonTest extends TestCase {
    use RefreshDatabase;

    private function asToken(User $user) {
        $token = $user->createToken('learner-test')->plainTextToken;
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_account_returns_json_without_accept_header(): void {
        $user = User::factory()->create(['is_active' => true]);
        $this->asToken($user)->get('/api/learner/account')->assertOk()
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_saved_returns_json_without_accept_header(): void {
        $user = User::factory()->create(['is_active' => true]);
        $this->asToken($user)->get('/api/learner/saved')->assertOk()
            ->assertJsonStructure(['data', 'next_page']);
    }

    public function test_practice_start_returns_json_without_accept_header(): void {
        $user = User::factory()->create(['is_active' => true]);
        // No published questions exist in this fresh test DB, so the
        // controller must answer 422 JSON — not a 500 HTML page and not
        // a redirect to the home page.
        $this->asToken($user)->post('/api/learner/practice', [
            'count' => 10, 'language' => 'en', 'category' => 'Traffic signs',
        ])->assertStatus(422)->assertJsonStructure(['message']);
    }
}
