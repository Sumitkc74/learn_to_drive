<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_admin_can_edit_other_admin_accounts(): void
    {
        $seedAdmin = User::where('email', 'admin@admin.com')->firstOrFail();

        $targetAdmin = User::create([
            'name' => 'Target Admin',
            'email' => 'target-admin@example.com',
            'phoneNumber' => '9800000001',
            'role' => 'Admin',
            'password' => Hash::make('password'),
            'profileImage' => 'demo',
        ]);

        $this->actingAs($seedAdmin);

        $response = $this->post('/admin/update-user/' . $targetAdmin->id, [
            'name' => 'Updated Admin',
            'email' => 'target-admin-updated@example.com',
            'phoneNumber' => '9800000002',
            'role' => 'Admin',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'id' => $targetAdmin->id,
            'name' => 'Updated Admin',
            'email' => 'target-admin-updated@example.com',
        ]);
    }

    public function test_non_seed_admin_cannot_edit_admin_accounts(): void
    {
        $admin = User::create([
            'name' => 'Regular Admin',
            'email' => 'regular-admin@example.com',
            'phoneNumber' => '9800000003',
            'role' => 'Admin',
            'password' => Hash::make('password'),
            'profileImage' => 'demo',
        ]);

        $targetAdmin = User::create([
            'name' => 'Protected Admin',
            'email' => 'protected-admin@example.com',
            'phoneNumber' => '9800000004',
            'role' => 'Admin',
            'password' => Hash::make('password'),
            'profileImage' => 'demo',
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/update-user/' . $targetAdmin->id, [
            'name' => 'Should Not Update',
            'email' => 'protected-admin-updated@example.com',
            'phoneNumber' => '9800000005',
            'role' => 'Admin',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', [
            'id' => $targetAdmin->id,
            'name' => 'Protected Admin',
            'email' => 'protected-admin@example.com',
        ]);
    }

    public function test_seed_admin_has_no_row_actions_and_cannot_be_managed_directly(): void
    {
        $seedAdmin = User::where('is_seed_admin', true)->firstOrFail();

        $this->actingAs($seedAdmin)
            ->get('/admin/users')
            ->assertOk()
            ->assertDontSee('/admin/edit-user/' . $seedAdmin->id, false)
            ->assertDontSee('/admin/delete-user/' . $seedAdmin->id, false)
            ->assertSee('Managed through Profile Settings');

        $this->actingAs($seedAdmin)
            ->get('/admin/edit-user/' . $seedAdmin->id)
            ->assertForbidden();

        $this->actingAs($seedAdmin)
            ->delete('/admin/delete-user/' . $seedAdmin->id)
            ->assertForbidden();
    }

    public function test_non_seed_admin_can_manage_users_but_not_other_admins(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $otherAdmin = User::factory()->create(['role' => 'Admin']);
        $regularUser = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($admin)->get('/admin/users')->assertOk();

        $response->assertSee('/admin/edit-user/' . $regularUser->id, false)
            ->assertSee('/admin/delete-user/' . $regularUser->id, false)
            ->assertDontSee('/admin/edit-user/' . $otherAdmin->id, false)
            ->assertDontSee('/admin/delete-user/' . $otherAdmin->id, false);

        $this->actingAs($admin)
            ->delete('/admin/delete-user/' . $otherAdmin->id)
            ->assertForbidden();
    }

    public function test_non_seed_admin_cannot_create_or_promote_an_admin(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $regularUser = User::factory()->create(['role' => 'User']);

        $this->actingAs($admin)->post('/admin/insert-user', [
            'name' => 'Unauthorized Admin',
            'email' => 'unauthorized-admin@example.com',
            'phoneNumber' => '9800000098',
            'role' => 'Admin',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('role');

        $this->actingAs($admin)->post('/admin/update-user/' . $regularUser->id, [
            'name' => $regularUser->name,
            'email' => $regularUser->email,
            'phoneNumber' => $regularUser->phoneNumber,
            'role' => 'Admin',
        ])->assertSessionHasErrors('role');

        $this->assertSame('User', $regularUser->fresh()->role);
    }
}
