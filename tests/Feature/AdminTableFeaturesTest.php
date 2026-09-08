<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTableFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_table_can_search_and_filter_by_role(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        User::factory()->create([
            'name' => 'Searchable Learner',
            'email' => 'searchable@example.com',
            'role' => 'User',
        ]);
        User::factory()->create([
            'name' => 'Different Premium Account',
            'email' => 'premium@example.com',
            'role' => 'PremiumUser',
        ]);

        $this->actingAs($admin)
            ->get(route('allUser', ['search' => 'Searchable', 'role' => 'User']))
            ->assertOk()
            ->assertSee('Filters &amp; sorting', false)
            ->assertSee('1 active')
            ->assertSee('Searchable Learner')
            ->assertDontSee('Different Premium Account');
    }

    public function test_user_table_is_paginated_and_preserves_query_parameters(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        User::factory()->count(12)->create(['role' => 'User']);

        $this->actingAs($admin)
            ->get(route('allUser', [
                'role' => 'User',
                'sort' => 'name',
                'direction' => 'asc',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertSee('Showing 1–10 of 12 records')
            ->assertSee('Page 1 of 2')
            ->assertSee('role=User', false)
            ->assertSee('sort=name', false);
    }

    public function test_user_table_can_filter_unverified_email_accounts(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        User::factory()->unverified()->create(['name' => 'Needs Email Verification']);
        User::factory()->create(['name' => 'Verified Email Account']);

        $this->actingAs($admin)
            ->get(route('allUser', ['verification' => 'email_unverified']))
            ->assertOk()
            ->assertSee('Needs Email Verification')
            ->assertDontSee('Verified Email Account');
    }

    public function test_unknown_sort_columns_and_page_sizes_fall_back_safely(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('allUser', [
                'sort' => 'password',
                'direction' => 'drop-table',
                'per_page' => 100000,
            ]))
            ->assertOk();
    }

    public function test_question_table_searches_answer_text(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        Question::create([
            'question' => 'Which sign requires a complete stop?',
            'option1' => 'Stop sign',
            'option2' => 'Speed limit',
            'option3' => 'No parking',
            'option4' => 'Hospital',
            'correctOption' => 'A',
        ]);
        Question::create([
            'question' => 'When should headlights be used?',
            'option1' => 'At night',
            'option2' => 'Never',
            'option3' => 'Only while parked',
            'option4' => 'At noon only',
            'correctOption' => 'A',
        ]);

        $this->actingAs($admin)
            ->get(route('allQuestion', ['search' => 'Speed limit']))
            ->assertOk()
            ->assertSee('Which sign requires a complete stop?')
            ->assertDontSee('When should headlights be used?');
    }
}
