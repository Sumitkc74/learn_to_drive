<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use App\Services\PdfTextTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_translate_every_text_field_in_both_directions_without_saving(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $translator = $this->mock(PdfTextTranslator::class);
        $translator->shouldReceive('ready')->andReturn(true);
        $fields = ['question', 'option1', 'option2', 'option3', 'option4', 'explanation'];
        $before = Question::count();
        foreach (['en' => 'ne', 'ne' => 'en'] as $source => $target) {
            foreach ($fields as $field) {
                $translator->shouldReceive('translate')->once()->with('Example '.$field, $source, $target)->andReturn('Translated '.$field);
                $this->postJson(route('questionTranslation'), ['field' => $field, 'text' => 'Example '.$field, 'source_language' => $source])
                    ->assertOk()->assertJsonPath('field', $field)->assertJsonPath('text', 'Translated '.$field);
            }
        }
        $this->assertSame($before, Question::count());
        $this->get(route('addQuestion'))->assertOk()->assertSee('Translate All Details');
    }

    public function test_translation_is_admin_only_and_cannot_translate_structural_fields(): void
    {
        $this->postJson(route('questionTranslation'))->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'User']))->postJson(route('questionTranslation'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->postJson(route('questionTranslation'), ['field' => 'correctOption', 'text' => 'A', 'source_language' => 'en'])->assertUnprocessable();
        $this->postJson(route('questionTranslation'), ['field' => 'option1', 'text' => str_repeat('x', 256), 'source_language' => 'en'])->assertUnprocessable();
    }

    public function test_provider_failure_returns_safe_error_and_long_output_is_not_truncated(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $translator = $this->mock(PdfTextTranslator::class);
        $translator->shouldReceive('ready')->andReturn(true);
        $translator->shouldReceive('translate')->once()->with('Stop', 'en', 'ne')->andReturn(str_repeat('क', 300));
        $this->postJson(route('questionTranslation'), ['field' => 'option1', 'text' => 'Stop', 'source_language' => 'en'])
            ->assertOk()->assertJsonPath('text', str_repeat('क', 300))->assertJsonPath('max_length', 255);
        $translator->shouldReceive('translate')->once()->with('Failure', 'en', 'ne')->andThrow(new \RuntimeException('private provider detail'));
        $this->postJson(route('questionTranslation'), ['field' => 'question', 'text' => 'Failure', 'source_language' => 'en'])
            ->assertStatus(502)->assertDontSee('private provider detail');
    }
}
