<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBankFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'question' => 'What should a driver do at a stop sign?',
            'option1' => 'Stop completely',
            'option2' => 'Speed up',
            'option3' => 'Ignore it',
            'option4' => 'Sound the horn',
            'correctOption' => 'A',
            'category' => 'Road Signs',
            'difficulty' => 'Easy',
            'explanation' => 'A stop sign requires a complete stop before proceeding safely.',
            'status' => 'Published',
        ], $overrides);
    }

    public function test_admin_can_create_a_question_with_learning_fields(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->post(route('insertQuestion'), $this->payload())
            ->assertRedirect(route('allQuestion'));

        $this->assertDatabaseHas('questions', [
            'category' => 'Road Signs',
            'difficulty' => 'Easy',
            'status' => 'Published',
        ]);
    }

    public function test_question_fields_reject_unapproved_values(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->post(route('insertQuestion'), $this->payload([
            'category' => 'Unknown',
            'difficulty' => 'Impossible',
            'status' => 'Hidden',
        ]))->assertSessionHasErrors(['category', 'difficulty', 'status']);
    }

    public function test_api_only_returns_published_questions(): void
    {
        Question::create($this->payload(['question' => 'Visible question']));
        Question::create($this->payload(['question' => 'Draft question', 'status' => 'Draft']));
        Question::create($this->payload(['question' => 'Archived question', 'status' => 'Archived']));

        $this->getJson('/api/question')
            ->assertOk()
            ->assertJsonFragment(['question' => 'Visible question'])
            ->assertJsonMissing(['question' => 'Draft question'])
            ->assertJsonMissing(['question' => 'Archived question']);
    }

    public function test_admin_can_filter_questions_by_learning_fields(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        Question::create($this->payload(['question' => 'Easy signs question']));
        Question::create($this->payload(['question' => 'Hard safety question', 'category' => 'Road Safety', 'difficulty' => 'Hard']));

        $this->actingAs($admin)->get(route('allQuestion', ['category' => 'Road Safety', 'difficulty' => 'Hard']))
            ->assertOk()
            ->assertSee('Hard safety question')
            ->assertDontSee('Easy signs question');
    }
}
