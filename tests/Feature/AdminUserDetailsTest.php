<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_regular_user_details_and_score_summary(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $user = User::factory()->create(['role' => 'User']);
        UserHistory::create([
            'user_id' => $user->id,
            'attempted_questions' => json_encode([1, 2]),
            'optionA' => '[]', 'optionB' => '[]', 'optionC' => '[]', 'optionD' => '[]',
            'correct_options' => json_encode(['A', 'B']),
            'selected_options' => json_encode(['A', 'C']),
        ]);

        $this->actingAs($admin)->get(route('showUser', $user->id))
            ->assertOk()->assertSee($user->name)->assertSee('50%')->assertSee('Exam and Learning History');
    }

    public function test_non_seed_admin_cannot_view_an_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'is_seed_admin' => false]);
        $otherAdmin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->get(route('showUser', $otherAdmin->id))->assertForbidden();
    }

    public function test_seed_admin_can_view_another_admin_but_not_own_user_page(): void
    {
        $seed = User::factory()->create(['role' => 'Admin', 'is_seed_admin' => true]);
        $otherAdmin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($seed)->get(route('showUser', $otherAdmin->id))->assertOk();
        $this->actingAs($seed)->get(route('showUser', $seed->id))->assertForbidden();
    }
}
