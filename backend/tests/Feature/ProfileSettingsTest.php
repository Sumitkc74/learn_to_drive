<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail;
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

    public function test_profile_page_displays_one_card_with_inline_edit_controls(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'Admin']);

        $this->actingAs($user)->get('/admin/profile-settings')
            ->assertOk()
            ->assertSee('ltd-profile-card', false)
            ->assertSee('Full Name')
            ->assertSee('Email Address')
            ->assertSee('Phone Number')
            ->assertSee('Not verified')
            ->assertSee('data-target="#nameModal"', false)
            ->assertSee('data-target="#emailModal"', false)
            ->assertSee('data-target="#phoneModal"', false)
            ->assertSee('data-target="#passwordModal"', false);
    }

    public function test_admin_can_update_each_profile_value_independently(): void
    {
        $user = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $this->patch('/admin/profile-settings/name', ['name' => 'Updated Name'])
            ->assertRedirect();
        $this->assertSame('Updated Name', $user->fresh()->name);

        $this->patch('/admin/profile-settings/phone', ['phoneNumber' => '9876543210'])
            ->assertRedirect();
        $user->refresh();
        $this->assertSame('9876543210', $user->phoneNumber);
        $this->assertNull($user->phone_verified_at);

        $this->patch('/admin/profile-settings/email', ['email' => 'updated@example.com'])
            ->assertRedirect();
        $user->refresh();
        $this->assertSame('updated@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_admin_can_verify_their_email_address(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['role' => 'Admin']);

        $this->actingAs($user)
            ->post('/admin/profile-settings/email/verification-notification')
            ->assertRedirect();
        Notification::assertSentTo($user, VerifyEmail::class);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->actingAs($user)->get($verificationUrl)
            ->assertRedirect(route('profileSettings'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_admin_can_verify_their_phone_with_a_local_code(): void
    {
        $user = User::factory()->create([
            'role' => 'Admin',
            'phone_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post('/admin/profile-settings/phone/verification-code')
            ->assertRedirect();
        $code = $response->getSession()->get('local_phone_otp');

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->actingAs($user)
            ->post('/admin/profile-settings/phone/verify', ['code' => $code])
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNull($user->phone_verification_code);
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
