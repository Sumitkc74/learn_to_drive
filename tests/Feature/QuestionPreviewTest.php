<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge(['question' => 'What does this sign mean?', 'option1' => 'Stop', 'option2' => 'Go', 'option3' => 'Park', 'option4' => 'Turn', 'correctOption' => 'A', 'category' => 'Road Signs', 'difficulty' => 'Easy', 'explanation' => 'The sign requires a complete stop.', 'status' => 'Draft'], $overrides);
    }

    public function test_admin_can_preview_question_and_reveal_explanation(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $question = Question::create($this->payload());
        $this->actingAs($admin)->get(route('previewQuestion', $question->id))->assertOk()->assertSee('Learner Preview')->assertSee('What does this sign mean?')->assertSee('Correct answer: Option A')->assertSee('The sign requires a complete stop.')->assertSee('not visible to learners');
    }

    public function test_admin_cannot_create_duplicate_question_text(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        Question::create($this->payload());
        $this->actingAs($admin)->post(route('insertQuestion'), $this->payload())->assertSessionHasErrors('question');
        $this->assertSame(1, Question::count());
    }

    public function test_question_can_be_updated_without_failing_its_own_unique_check(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $question = Question::create($this->payload());
        $this->actingAs($admin)->post(route('updateQuestion', $question->id), $this->payload(['difficulty' => 'Hard']))->assertRedirect(route('allQuestion'));
        $this->assertSame('Hard', $question->fresh()->difficulty);
    }
}
