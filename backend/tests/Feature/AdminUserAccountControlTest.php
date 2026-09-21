<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserAccountControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_suspend_user_and_revoke_tokens(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $user = User::factory()->create(['role' => 'User']);
        $user->createToken('mobile');

        $this->actingAs($admin)->patch(route('suspendUser', $user->id), ['suspension_reason' => 'Policy review'])->assertRedirect();
        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertSame('Policy review', $user->suspension_reason);
        $this->assertCount(0, $user->tokens);
    }

    public function test_suspended_user_cannot_log_in_through_api(): void
    {
        $user = User::factory()->create(['is_active' => false, 'password' => bcrypt('password123')]);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password123'])->assertForbidden()->assertJsonFragment(['message' => 'This account is suspended. Contact an administrator.']);
    }

    public function test_admin_can_reactivate_user(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $user = User::factory()->create(['role' => 'User', 'is_active' => false, 'suspended_at' => now(), 'suspension_reason' => 'Review']);
        $this->actingAs($admin)->patch(route('reactivateUser', $user->id))->assertRedirect();
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_non_seed_admin_cannot_suspend_another_admin(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'is_seed_admin' => false]);
        $otherAdmin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->patch(route('suspendUser', $otherAdmin->id))->assertForbidden();
    }
}
