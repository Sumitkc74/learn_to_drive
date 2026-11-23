<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LearnerAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_change_avatar(): void
    {
        $this->patch('/learn/settings/avatar', ['preset' => 'avatar-1'])
            ->assertRedirect(route('learn.login'));
    }

    public function test_choosing_a_preset_changes_the_avatar(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)
            ->patch('/learn/settings/avatar', ['preset' => 'avatar-3'])
            ->assertRedirect(route('learn.settings'))
            ->assertSessionHas('success');

        $user = $user->fresh();
        $this->assertSame(1, $user->media()->count());
        $this->assertStringContainsString('avatar-3', $user->avatar_url);
    }

    public function test_choosing_a_preset_replaces_the_previous_avatar(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);
        $this->patch('/learn/settings/avatar', ['preset' => 'avatar-1'])->assertRedirect(route('learn.settings'));
        $this->patch('/learn/settings/avatar', ['preset' => 'avatar-5'])->assertRedirect(route('learn.settings'));

        $user = $user->fresh();
        $this->assertSame(1, $user->media()->count());
        $this->assertStringContainsString('avatar-5', $user->avatar_url);
        $this->assertStringNotContainsString('avatar-1', $user->avatar_url);
    }

    public function test_uploading_a_photo_works(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)
            ->patch('/learn/settings/avatar', ['photo' => UploadedFile::fake()->image('photo.png')])
            ->assertRedirect(route('learn.settings'))
            ->assertSessionHas('success');

        $user = $user->fresh();
        $this->assertSame(1, $user->media()->count());
        $this->assertStringContainsString('photo.png', $user->avatar_url);
    }

    public function test_uploading_a_photo_replaces_a_preset_avatar(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);
        $this->patch('/learn/settings/avatar', ['preset' => 'avatar-2'])->assertRedirect(route('learn.settings'));
        $this->patch('/learn/settings/avatar', ['photo' => UploadedFile::fake()->image('photo.png')])
            ->assertRedirect(route('learn.settings'));

        $user = $user->fresh();
        $this->assertSame(1, $user->media()->count());
        $this->assertStringContainsString('photo.png', $user->avatar_url);
    }

    public function test_invalid_preset_is_rejected_with_no_change(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)
            ->patch('/learn/settings/avatar', ['preset' => 'avatar-99'])
            ->assertSessionHasErrors('preset');

        $this->assertSame(0, $user->fresh()->media()->count());
    }

    public function test_neither_preset_nor_photo_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)
            ->patch('/learn/settings/avatar', [])
            ->assertSessionHasErrors('avatar');

        $this->assertSame(0, $user->fresh()->media()->count());
    }

    public function test_preset_and_photo_together_is_rejected_with_no_change(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)
            ->patch('/learn/settings/avatar', [
                'preset' => 'avatar-1',
                'photo' => UploadedFile::fake()->image('photo.png'),
            ])
            ->assertSessionHasErrors('avatar');

        $this->assertSame(0, $user->fresh()->media()->count());
    }

    public function test_settings_page_shows_the_avatar_choices(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->get('/learn/settings')
            ->assertOk()
            ->assertSee('Profile photo')
            ->assertSee('avatar-1.svg')
            ->assertSee('avatar-6.svg');
    }
}
