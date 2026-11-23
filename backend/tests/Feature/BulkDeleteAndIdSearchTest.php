<?php

namespace Tests\Feature;

use App\Models\DrivingSchool;
use App\Models\ExamPaper;
use App\Models\Notice;
use App\Models\Question;
use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkDeleteAndIdSearchTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }

    private function makeQuestion(string $text): Question
    {
        return Question::create([
            'question' => $text,
            'option1' => 'Alpha',
            'option2' => 'Beta',
            'option3' => 'Gamma',
            'option4' => 'Delta',
            'correctOption' => 'A',
            'category' => 'General',
            'difficulty' => 'Easy',
            'status' => 'Published',
        ]);
    }

    public function test_numeric_search_finds_a_question_by_id(): void
    {
        $this->admin();
        $wanted = $this->makeQuestion('Which sign means stop ahead?');
        $other = $this->makeQuestion('When must headlights be switched on?');

        $this->get(route('allQuestion', ['search' => (string) $wanted->id]))
            ->assertOk()
            ->assertSee('Which sign means stop ahead?')
            ->assertDontSee('When must headlights be switched on?');
    }

    public function test_text_search_still_matches_question_text(): void
    {
        $this->admin();
        $this->makeQuestion('Which sign means stop ahead?');
        $this->makeQuestion('When must headlights be switched on?');

        $this->get(route('allQuestion', ['search' => 'headlights']))
            ->assertOk()
            ->assertSee('When must headlights be switched on?')
            ->assertDontSee('Which sign means stop ahead?');
    }

    public function test_numeric_search_finds_a_school_by_id(): void
    {
        $this->admin();
        $wanted = DrivingSchool::create(['name' => 'Riverside Driving Centre']);
        DrivingSchool::create(['name' => 'Hilltop Motor School']);

        $this->get(route('admin.schools.index', ['search' => (string) $wanted->id]))
            ->assertOk()
            ->assertSee('Riverside Driving Centre')
            ->assertDontSee('Hilltop Motor School');
    }

    public function test_bulk_delete_moves_selected_questions_to_trash_only(): void
    {
        $this->admin();
        $first = $this->makeQuestion('First bulk question');
        $second = $this->makeQuestion('Second bulk question');
        $kept = $this->makeQuestion('Kept bulk question');

        $this->delete(route('bulkDeleteQuestions'), ['ids' => [$first->id, $second->id]])
            ->assertRedirect()
            ->assertSessionHas('success', '2 questions moved to Trash.');

        $this->assertSoftDeleted('questions', ['id' => $first->id]);
        $this->assertSoftDeleted('questions', ['id' => $second->id]);
        $this->assertDatabaseHas('questions', ['id' => $kept->id, 'deleted_at' => null]);
    }

    public function test_bulk_delete_deletes_only_selected_tutorials(): void
    {
        $this->admin();
        $deleted = Tutorial::create(['title' => 'Bulk tutorial', 'description' => 'Details', 'videoLink' => 'https://example.com/video']);
        $kept = Tutorial::create(['title' => 'Kept tutorial', 'description' => 'Details', 'videoLink' => 'https://example.com/video']);

        $this->delete(route('bulkDeleteTutorials'), ['ids' => [$deleted->id]])
            ->assertRedirect()
            ->assertSessionHas('success', '1 tutorial deleted.');

        $this->assertDatabaseMissing('tutorials', ['id' => $deleted->id]);
        $this->assertDatabaseHas('tutorials', ['id' => $kept->id]);
    }

    public function test_bulk_delete_moves_selected_notices_to_trash_only(): void
    {
        $this->admin();
        $deleted = Notice::create(['title' => 'Bulk notice', 'description' => 'Details', 'nepaliTitle' => 'Notice', 'nepaliDescription' => 'Details']);
        $kept = Notice::create(['title' => 'Kept notice', 'description' => 'Details', 'nepaliTitle' => 'Notice', 'nepaliDescription' => 'Details']);

        $this->delete(route('bulkDeleteNotices'), ['ids' => [$deleted->id]])
            ->assertRedirect()
            ->assertSessionHas('success', '1 notice moved to Trash.');

        $this->assertSoftDeleted('notices', ['id' => $deleted->id]);
        $this->assertDatabaseHas('notices', ['id' => $kept->id, 'deleted_at' => null]);
    }

    public function test_bulk_delete_routes_exist_for_the_other_content_types(): void
    {
        $this->admin();
        $bank = ExamPaper::create(['name' => 'Bulk bank', 'description' => 'Practice', 'language' => 'English']);

        $this->delete(route('bulkDeleteExamPapers'), ['ids' => [$bank->id]])
            ->assertRedirect()
            ->assertSessionHas('success', '1 question bank deleted.');

        $this->assertDatabaseMissing('exam_papers', ['id' => $bank->id]);
    }

    public function test_bulk_delete_requires_admin(): void
    {
        $question = $this->makeQuestion('Admin-only bulk question');

        // Guests are sent to sign in.
        $this->delete(route('bulkDeleteQuestions'), ['ids' => [$question->id]])
            ->assertRedirect(route('login'));
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'deleted_at' => null]);

        // Signed-in non-admin users are forbidden.
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)
            ->delete(route('bulkDeleteQuestions'), ['ids' => [$question->id]])
            ->assertForbidden();
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'deleted_at' => null]);
    }

    public function test_bulk_delete_rejects_empty_and_invalid_id_lists(): void
    {
        $this->admin();
        $question = $this->makeQuestion('Validation bulk question');

        $this->delete(route('bulkDeleteQuestions'), ['ids' => []])
            ->assertSessionHasErrors('ids');
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'deleted_at' => null]);

        $this->delete(route('bulkDeleteQuestions'), ['ids' => [999999]])
            ->assertSessionHasErrors('ids.0');
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'deleted_at' => null]);

        $this->delete(route('bulkDeleteQuestions'), ['ids' => ['not-an-id']])
            ->assertSessionHasErrors('ids.0');
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'deleted_at' => null]);
    }

    public function test_question_list_renders_the_bulk_delete_bar(): void
    {
        $this->admin();
        $this->makeQuestion('Bar render question');

        $this->get(route('allQuestion'))
            ->assertOk()
            ->assertSee('bulk-delete-form', false)
            ->assertSee('bulk-delete-select-all', false)
            ->assertSee('bulk-delete-row', false)
            ->assertSee('Delete selected');
    }
}
