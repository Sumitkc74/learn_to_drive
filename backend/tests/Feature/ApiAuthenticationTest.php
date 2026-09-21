<?php

namespace Tests\Feature;

use App\Models\AppSetting;
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

    public function test_login_uses_configured_api_session_lifetime(): void
    {
        AppSetting::updateOrCreate(['key' => 'access_token_expiry_days'], ['value' => '14']);
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])->assertOk();

        $this->assertTrue($user->tokens()->first()->expires_at->between(now()->addDays(13), now()->addDays(15)));
    }

    public function test_password_change_uses_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $other = $user->createToken('other')->accessToken;
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->putJson('/api/auth/password', [
            'current_password' => 'password',
            'new_password' => 'new-password-123',
            'new_password_confirmation' => 'new-password-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('personal_access_tokens', ['id'=>$other->id]);
        $this->assertCount(1, $user->tokens()->get());
    }

    public function test_login_limits_account_even_when_source_ip_changes(): void
    {
        for ($i=1; $i<=5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR'=>'192.0.2.'.$i])->postJson('/api/auth/login', ['email'=>'rate-limit@example.test','password'=>'wrong'])->assertUnauthorized();
        }
        $this->withServerVariables(['REMOTE_ADDR'=>'192.0.2.6'])->postJson('/api/auth/login', ['email'=>'RATE-LIMIT@example.test','password'=>'wrong'])->assertStatus(429);
    }

    public function test_untrusted_forwarded_headers_do_not_spoof_client_ip(): void
    {
        $request = \Illuminate\Http\Request::create('http://example.test/api/user', 'GET', [], [], [], ['REMOTE_ADDR'=>'192.0.2.1','HTTP_X_FORWARDED_FOR'=>'203.0.113.9','HTTP_X_FORWARDED_PROTO'=>'https']);
        config(['network.trusted_proxies'=>[]]);
        (new \App\Http\Middleware\TrustProxies)->handle($request, function ($request) {
            $this->assertSame('192.0.2.1', $request->ip());
            $this->assertFalse($request->isSecure());
            return response('ok');
        });
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
