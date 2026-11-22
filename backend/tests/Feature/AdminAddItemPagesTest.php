<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAddItemPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_add_item_pages_render_the_shared_form_layout(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $routes = [
            'addUser',
            'addQuestion',
            'addTrafficSign',
            'addVisionTest',
            'addTutorial',
            'addNotice',
            'addExamPaper',
            'addExamInformation',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertSee('data-add-form', false)
                ->assertSee('ltd-form-card', false)
                ->assertSee('ltd-form-actions', false)
                ->assertSee('ltd-form-section__title', false)
                ->assertSee('data-submit-button', false);
        }
    }

    public function test_add_pages_use_titled_sections_and_a_consistent_submit_action(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->get(route('addTutorial'))
            ->assertOk()
            ->assertSee('Tutorial details')
            ->assertSee('Add Tutorial');

        $this->actingAs($admin)->get(route('addVisionTest'))
            ->assertOk()
            ->assertSee('Test details')
            ->assertSee('Add Vision Test');

        $this->actingAs($admin)->get(route('addNotice'))
            ->assertOk()
            ->assertSee('Add Notice');
    }

    public function test_schools_index_create_form_uses_the_shared_section_layout(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->get(route('admin.schools.index'))
            ->assertOk()
            ->assertSee('ltd-form-section__title', false)
            ->assertSee('School details')
            ->assertSee('Map location')
            ->assertSee('School logo and photos')
            ->assertSee('Create school');
    }

    public function test_tutorial_rejects_an_invalid_video_url_but_not_a_missing_thumbnail(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->from(route('addTutorial'))
            ->post(route('insertTutorial'), [
                'title' => 'Parallel parking',
                'description' => 'A practical parking tutorial.',
                'videoLink' => 'not-a-url',
            ])
            ->assertRedirect(route('addTutorial'))
            ->assertSessionHasErrors(['videoLink'])
            ->assertSessionDoesntHaveErrors(['image']);
    }

    public function test_user_creation_rejects_an_unknown_role_and_unconfirmed_password(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->from(route('addUser'))
            ->post(route('insertUser'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phoneNumber' => '9812345678',
                'role' => 'SuperAdmin',
                'password' => 'secure-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertRedirect(route('addUser'))
            ->assertSessionHasErrors(['role', 'password']);
    }

    public function test_user_created_without_a_photo_uses_the_default_avatar(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->post(route('insertUser'), [
            'name' => 'No Photo User',
            'email' => 'no-photo@example.com',
            'phoneNumber' => '9812345678',
            'role' => 'User',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect(route('allUser'));

        $user = User::where('email', 'no-photo@example.com')->firstOrFail();

        $this->assertSame('dist/img/avatar.png', $user->profileImage);
        $this->assertSame(asset('dist/img/avatar.png'), $user->avatar_url);
        $this->assertFalse($user->hasMedia());
    }
}
