<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedOptionFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_role_field_is_a_dropdown_with_the_system_roles(): void
    {
        // The legacy auth/register view cannot be rendered: no `register`
        // route is registered (only learn.register and api/auth/register
        // exist), so the view is verified from its source instead.
        $source = file_get_contents(resource_path('views/auth/register.blade.php'));

        $this->assertStringContainsString('<select', $source);
        $this->assertStringContainsString('name="role"', $source);
        $this->assertStringContainsString('<option value="User"', $source);
        $this->assertStringContainsString('<option value="PremiumUser"', $source);
        $this->assertStringContainsString('<option value="Admin"', $source);
        // The role field must not accept free text any more (name and phone
        // stay text inputs, as they should).
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="role"/', $source);
    }

    public function test_question_form_renders_fixed_fields_as_dropdowns(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->get(route('addQuestion'))
            ->assertOk()
            ->assertSee('<select id="category" name="category"', false)
            ->assertSee('<select id="difficulty" name="difficulty"', false)
            ->assertSee('<select id="status" name="status"', false)
            ->assertSee('<select id="correctOption" name="correctOption"', false)
            ->assertSee('<option value="General"', false)
            ->assertSee('<option value="Road Safety"', false)
            ->assertSee('<option value="Hard"', false)
            ->assertSee('<option value="Published"', false)
            ->assertSee('<option value="D"', false);
    }

    public function test_question_form_submit_still_works_with_dropdown_values(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->post(route('insertQuestion'), [
            'question' => 'What must a driver do at a red traffic light?',
            'option1' => 'Stop',
            'option2' => 'Slow down and continue',
            'option3' => 'Speed up',
            'option4' => 'Turn around',
            'correctOption' => 'A',
            'category' => 'Traffic Rules',
            'difficulty' => 'Easy',
            'explanation' => 'A red light means stop.',
            'status' => 'Published',
        ])->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'question' => 'What must a driver do at a red traffic light?',
            'correctOption' => 'A',
            'category' => 'Traffic Rules',
            'difficulty' => 'Easy',
            'status' => 'Published',
        ]);
        $this->assertSame(1, Question::count());
    }
}
