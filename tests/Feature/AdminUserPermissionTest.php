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
}
