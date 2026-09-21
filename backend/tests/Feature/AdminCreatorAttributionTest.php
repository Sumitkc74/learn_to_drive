<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCreatorAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_content_records_and_displays_its_creator(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);

        $question = Question::create([
            'question' => 'Who added this question?',
            'option1' => 'One',
            'option2' => 'Two',
            'option3' => 'Three',
            'option4' => 'Four',
            'correctOption' => 'A',
            'category' => 'General',
            'difficulty' => 'Easy',
            'status' => 'Draft',
        ]);

        $this->assertSame($admin->id, $question->created_by);

        $this->get(route('allQuestion'))
            ->assertOk()
            ->assertSee('Added by')
            ->assertSee($admin->name);
    }

    public function test_system_created_content_does_not_claim_an_admin_creator(): void
    {
        $question = Question::create([
            'question' => 'Imported before attribution',
            'option1' => 'One',
            'option2' => 'Two',
            'option3' => 'Three',
            'option4' => 'Four',
            'correctOption' => 'A',
        ]);

        $this->assertNull($question->created_by);
    }
}
