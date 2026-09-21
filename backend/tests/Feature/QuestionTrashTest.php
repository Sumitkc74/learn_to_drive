<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionTrashTest extends TestCase
{
    use RefreshDatabase;

    private function question(): Question
    {
        return Question::create([
            'question' => 'Recoverable question',
            'option1' => 'A', 'option2' => 'B', 'option3' => 'C', 'option4' => 'D',
            'correctOption' => 'A', 'category' => 'General', 'difficulty' => 'Medium',
            'status' => 'Published',
        ]);
    }

    public function test_deleting_a_question_moves_it_to_trash(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $question = $this->question();

        $this->actingAs($admin)->delete(route('deleteQuestion', $question->id))->assertRedirect(route('allQuestion'));

        $this->assertSoftDeleted($question);
        $this->actingAs($admin)->get(route('questionTrash'))->assertOk()->assertSee('Recoverable question');
    }

    public function test_admin_can_restore_a_trashed_question(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $question = $this->question();
        $question->delete();

        $this->actingAs($admin)->patch(route('restoreQuestion', $question->id))->assertRedirect(route('questionTrash'));
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'deleted_at' => null]);
    }

    public function test_admin_can_permanently_delete_a_trashed_question(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $question = $this->question();
        $question->delete();

        $this->actingAs($admin)->delete(route('forceDeleteQuestion', $question->id))->assertRedirect(route('questionTrash'));
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }
}
