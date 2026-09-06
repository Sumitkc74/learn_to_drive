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

    public function test_profile_page_displays_individual_setting_cards(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($user)->get('/admin/profile-settings')
            ->assertOk()
            ->assertSee('Profile Photo')
            ->assertSee('Full Name')
            ->assertSee('Email Address')
            ->assertSee('Phone Number')
            ->assertSee('Password');
    }

    public function test_admin_can_update_each_profile_value_independently(): void
    {
        $user = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $this->patch('/admin/profile-settings/name', ['name' => 'Updated Name'])
            ->assertRedirect();
        $this->assertSame('Updated Name', $user->fresh()->name);

        $this->patch('/admin/profile-settings/phone', ['phoneNumber' => '9876543210'])
            ->assertRedirect();
        $this->assertSame('9876543210', $user->fresh()->phoneNumber);

        $this->patch('/admin/profile-settings/email', ['email' => 'updated@example.com'])
            ->assertRedirect();
        $user->refresh();
        $this->assertSame('updated@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_admin_must_confirm_the_current_password_before_changing_it(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($user)->patch('/admin/profile-settings/password', [
            'current_password' => 'incorrect-password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $this->actingAs($user)->patch('/admin/profile-settings/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }
}
