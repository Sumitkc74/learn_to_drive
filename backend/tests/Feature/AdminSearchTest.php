<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_search_finds_users_and_content_and_links_to_filtered_lists(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        User::factory()->create(['name' => 'Parking learner']);
        Question::create(['question' => 'Parking question', 'option1' => 'A', 'option2' => 'B', 'option3' => 'C', 'option4' => 'D', 'correctOption' => 'A']);
        Notice::create(['title' => 'Parking notice', 'description' => 'Details', 'nepaliTitle' => 'Notice', 'nepaliDescription' => 'Details']);
        $this->actingAs($admin)->get(route('adminSearch', ['q' => 'Parking']))->assertOk()
            ->assertSee('Parking learner')->assertSee('Parking question')->assertSee('Parking notice')
            ->assertSee(route('allQuestion', ['search' => 'Parking']), false)
            ->assertViewHas('sections', fn ($sections) => count($sections) === 3);
        $this->get(route('adminDashboard'))->assertSee('action="'.route('adminSearch').'"', false);
    }

    public function test_search_is_admin_only_and_validates_input(): void
    {
        $this->get(route('adminSearch', ['q' => 'test']))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'User']))->get(route('adminSearch', ['q' => 'test']))->assertForbidden();
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        $this->getJson(route('adminSearch', ['q' => ['invalid']]))->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson(route('adminSearch', ['q' => str_repeat('a', 101)]))->assertUnprocessable()->assertJsonValidationErrors('q');
    }

    public function test_search_handles_empty_no_results_and_section_names(): void
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        $this->get(route('adminSearch'))->assertOk()->assertSee('Enter a keyword');
        $this->get(route('adminSearch', ['q' => 'NonexistentKeyword']))->assertOk()->assertSee('No results for');
        $this->get(route('adminSearch', ['q' => 'Traffic Signs']))->assertOk()
            ->assertSee('Open Traffic Signs')->assertSee(route('allTrafficSign'), false);
    }

    public function test_results_are_bounded_and_exclude_trash(): void
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        for ($i = 0; $i < 7; $i++) {
            Question::create(['question' => 'Parking '.$i, 'option1' => 'A', 'option2' => 'B', 'option3' => 'C', 'option4' => 'D', 'correctOption' => 'A']);
        }
        Question::first()->delete();
        $this->get(route('adminSearch', ['q' => 'Parking']))->assertOk()
            ->assertViewHas('sections', fn ($sections) => $sections[0]['count'] === 6 && $sections[0]['items']->count() === 5)
            ->assertDontSee('Parking 0');
    }

    public function test_dropdown_controls_apply_search_filters_and_sort_together(): void
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        User::factory()->create(['name' => 'Parking premium', 'role' => 'PremiumUser']);
        User::factory()->create(['name' => 'Parking regular', 'role' => 'User']);
        $this->get(route('allUser', ['search' => 'Parking', 'role' => 'PremiumUser', 'sort' => 'name', 'direction' => 'asc', 'per_page' => 25]))
            ->assertOk()->assertSee('Parking premium')->assertDontSee('Parking regular')
            ->assertSee('ltd-filter-panel__body--row')->assertSee('<details class="ltd-filter-panel" open>', false)
            ->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->perPage() === 25);
    }
}
