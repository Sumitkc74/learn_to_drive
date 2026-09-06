<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_api_routes_require_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->getJson('/api/userHistory')->assertUnauthorized();
        $this->postJson('/api/userHistory')->assertUnauthorized();
        $this->putJson('/api/auth/password')->assertUnauthorized();
        $this->postJson('/api/auth/logout')->assertUnauthorized();
        $this->postJson('/api/payment')->assertUnauthorized();
    }

    public function test_login_returns_a_persisted_expiring_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['token' => ['access_token', 'token_type', 'expires_at']]);

        $this->assertNotNull($user->tokens()->first()->expires_at);
    }

    public function test_password_change_uses_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->putJson('/api/auth/password', [
            'current_password' => 'password',
            'new_password' => 'new-password-123',
            'new_password_confirmation' => 'new-password-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_logout_revokes_the_current_access_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_payment_upgrade_is_disabled_until_provider_verification_exists(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/payment')->assertStatus(503);
        $this->assertSame('User', $user->fresh()->role);
    }
}
