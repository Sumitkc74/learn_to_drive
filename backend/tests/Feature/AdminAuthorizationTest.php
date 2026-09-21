<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_regular_users_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admins_can_access_the_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }
}
