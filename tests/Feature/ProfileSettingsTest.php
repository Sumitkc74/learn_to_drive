<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_user_is_available_after_refresh(): void
    {
        $this->assertDatabaseHas('users', [
            'email' => 'admin@admin.com',
            'role' => 'Admin',
        ]);
    }

    public function test_admin_can_update_their_profile_details(): void
    {
        $user = User::create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phoneNumber' => '1234567890',
            'role' => 'Admin',
            'password' => Hash::make('password123'),
            'profileImage' => 'demo',
        ]);

        $this->actingAs($user);

        $response = $this->post('/admin/profile-settings/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phoneNumber' => '9876543210',
            'password' => 'newpassword123',
        ]);

        $response->assertRedirect('/admin/profile-settings');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phoneNumber' => '9876543210',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }
}
